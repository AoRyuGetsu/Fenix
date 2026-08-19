# 06 — Autenticación y Autorización (JWT)

Diagramas de secuencia que cubren los tres flujos principales de autenticación: login, refresh de token y validación de middleware.

## 6.1 Flujo de Login

```mermaid
sequenceDiagram
    participant C as Cliente (Angular)
    participant K as Kernel/Router
    participant A as AuthController
    participant AS as AuthService
    participant US as UserService
    participant PH as PasswordHasher
    participant RAC as RoleAccessControlService
    participant DB as MySQL

    C->>K: POST /auth/login { "email", "password" }
    K->>A: AuthController::login(Request)
    A->>A: json_decode($request->getContent())
    A->>A: Valida email y password presentes

    alt Campos faltantes
        A-->>C: 422 {"error": "VALIDATION_EMAIL_REQUIRED"}
    end

    A->>AS: AuthService::login(email, password)
    AS->>US: UserService::findByEmail(email)
    US->>DB: SELECT * FROM users WHERE email = ?
    DB-->>US: User entity
    US-->>AS: User o null

    alt Usuario no encontrado
        AS-->>A: AppException('INVALID_CREDENTIALS')
        A-->>C: 401 {"error": "Credenciales incorrectas", "code": "INVALID_CREDENTIALS"}
    end

    AS->>PH: PasswordHasher::verify(password, hash)
    PH->>PH: password_verify() con Argon2id

    alt Contraseña incorrecta
        PH-->>AS: false
        AS-->>A: AppException('INVALID_CREDENTIALS')
        A-->>C: 401 {"error": "Credenciales incorrectas", "code": "INVALID_CREDENTIALS"}
    end

    PH-->>AS: true
    AS->>RAC: getRoles(userId) + getPermissions(userId) + getMenus(userId)
    RAC->>DB: SELECT roles, permissions, menus
    DB-->>RAC: Datos de roles, permisos y menús
    RAC-->>AS: roleCodes[], permissionNames[], menus[]

    AS->>AS: createToken(sub, email, roles, permissions, accessTTL)
    Note over AS: JWT Builder<br>issuedBy (JWT_ISSUER)<br>issuedAt (UTC)<br>expiresAt (UTC + accessTTL)<br>claims: sub, email, roles, permissions

    AS->>AS: createToken(sub, email, roles, permissions, refreshTTL, 'refresh')
    Note over AS: Mismo builder + claim<br>purpose: 'refresh'

    AS-->>A: { access_token, refresh_token, expires_in,<br>user, roles, menus, catalogs }
    Note over A: user solo en login (includeUser=true)<br>payload NO incluye permissions (van en claims del token)

    A-->>C: 200 JsonResponse
    Note over C: Cliente almacena tokens en localStorage
```

## 6.2 Flujo de Refresh Token

```mermaid
sequenceDiagram
    participant C as Cliente (Angular)
    participant K as Kernel/Router
    participant A as AuthController
    participant AS as AuthService
    participant US as UserService

    C->>K: POST /auth/refresh { "refresh_token" }
    K->>A: AuthController::refresh(Request)
    A->>A: json_decode -> extrae refresh_token

    alt refresh_token faltante
        A-->>C: 422 {"error": "VALIDATION_REFRESH_REQUIRED"}
    end

    A->>AS: AuthService::refreshToken(refreshToken)
    AS->>AS: validateToken(refreshToken)
    AS->>AS: parse + validar firma HS256 + expiración
    AS->>AS: Verificar claim 'purpose' === 'refresh'

    alt Token inválido (firma/estructura)
        AS-->>A: AppException('TOKEN_INVALID')
        A-->>C: 401 {"error": "Token inválido", "code": "TOKEN_INVALID"}
    else Token expirado
        AS-->>A: AppException('TOKEN_EXPIRED')
        A-->>C: 401 {"error": "Token expirado", "code": "TOKEN_EXPIRED"}
    else Propósito incorrecto
        AS-->>A: AppException('INVALID_TOKEN_PURPOSE')
        A-->>C: 401 {"error": "Propósito de token inválido", "code": "INVALID_TOKEN_PURPOSE"}
    end

    AS->>US: UserService::findById(userId de claims)
    US-->>AS: User o null

    alt Usuario no encontrado
        AS-->>A: AppException('USER_NOT_FOUND')
        A-->>C: 404 {"error": "Usuario no encontrado", "code": "USER_NOT_FOUND"}
    end

    AS->>AS: buildAuthResponse(user, includeUser: false)
    Note over AS: Genera NUEVO par access_token + refresh_token<br>con fresh TTLs
    AS-->>A: { access_token, refresh_token, expires_in, roles, menus, catalogs }
    Note over A: Sin datos de user (includeUser=false)

    A-->>C: 200 JsonResponse
    Note over C: Cliente actualiza tokens en localStorage<br>y retry el request original
```

## 6.3 Validación en Middleware

```mermaid
sequenceDiagram
    participant C as Cliente (Angular)
    participant M as Middleware
    participant AS as AuthService
    participant K as Container
    participant EM as EntityManager
    participant CT as Controller

    C->>M: Request con Authorization: Bearer <token>
    M->>M: Extrae header Authorization

    alt Header ausente o sin Bearer
        M-->>C: 401 {"error": "Token required"}
    end

    M->>K: Container::get(AuthServiceInterface)
    M->>AS: AuthService::validateToken(token)
    AS->>AS: config->parser()->parse(token)
    AS->>AS: Verificar instancia Plain
    AS->>AS: Validar SignedWith (HS256)
    AS->>AS: Validar StrictValidAt (UTC clock)

    alt Token inválido
        AS-->>M: AppException('TOKEN_INVALID')
        M-->>C: 401 {"error": "Token inválido", "code": "TOKEN_INVALID"}
    else Token expirado
        AS-->>M: AppException('TOKEN_EXPIRED')
        M-->>C: 401 {"error": "Token expirado", "code": "TOKEN_EXPIRED"}
    end

    AS-->>M: claims[] (sub, email, roles, permissions)
    M->>M: Crea AuthContext(userId, email, roles, permissions)
    M->>K: Container::set(AuthContext::class, authContext)

    alt Roles requeridos (ej: ['ADM'])
        M->>M: Verificar authContext->hasRole('ADM')
        alt Sin rol requerido
            M-->>C: 403 {"error": "Forbidden"}
        end
    end

    alt Usuario es administrador
        M->>K: Container::get(EntityManager)
        M->>EM: em->getFilters()->disable('soft-delete')
        Note over EM: Admin puede ver<br>registros soft-deleteados
    end

    M->>CT: Continúa al handler original
    CT->>CT: Ejecuta lógica del controller
    CT-->>C: JsonResponse
```

## 6.4 Obtener Usuario Autenticado (`/auth/me`)

```mermaid
sequenceDiagram
    participant C as Cliente (Angular)
    participant M as Middleware
    participant A as AuthController
    participant AS as AuthService
    participant RAC as RoleAccessControlService

    C->>M: GET /auth/me (Authorization: Bearer)
    M->>M: Valida JWT + crea AuthContext
    M->>A: Controller::me() (vía middleware)
    A->>AS: AuthService::getMe(authContext)
    AS->>RAC: getRoles + getPermissions (userId)
    RAC-->>AS: roles[], permissionNames[]
    AS-->>A: { user, roles, permissions }
    A-->>C: 200 JsonResponse
```

- `GET /auth/me` requiere token y solo devuelve datos del usuario autenticado (no regenera tokens).
- Este endpoint es el único de infraestructura que expone `permissions` en el payload.

## Claims del Token JWT

| Claim | Tipo | Descripción |
|---|---|---|
| `iss` | string | Issuer (`JWT_ISSUER` del `.env`) |
| `iat` | DateTimeImmutable | Emitido en (UTC) |
| `nbf` | DateTimeImmutable | No válido antes de (UTC) |
| `exp` | DateTimeImmutable | Expira en (UTC) |
| `sub` | string | ID del usuario |
| `email` | string | Email del usuario |
| `roles` | string[] | Códigos de roles (`['ADM', 'DIR']`) |
| `permissions` | string[] | Nombres de permisos |
| `purpose` | string | Solo en refresh: `"refresh"` |

## Configuración via `.env`

| Variable | Default | Descripción |
|---|---|---|
| `JWT_SECRET` | — | Clave secreta (base64-encoded) |
| `JWT_ACCESS_TTL` | 3600 | TTL del access token en segundos |
| `JWT_REFRESH_TTL` | 604800 | TTL del refresh token en segundos |
| `JWT_ISSUER` | `analitica-api` | Issuer claim del token |
