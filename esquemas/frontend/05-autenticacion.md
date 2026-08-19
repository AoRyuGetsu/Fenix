# Flujo de Autenticación

Diagramas de secuencia que cubren el login exitoso, el refresh automático de tokens ante un 401, y el fallo del refresh.

## Login Exitoso

```mermaid
sequenceDiagram
    actor User as 👤 Usuario
    participant LoginPage as LoginPageComponent
    participant AuthFacade as AuthFacade
    participant AuthHttp as AuthHttpService
    participant API as Backend API
    participant Storage as localStorage

    User->>LoginPage: Ingresa email + password
    LoginPage->>LoginPage: form.getRawValue()
    LoginPage->>AuthFacade: login(email, password)

    AuthFacade->>AuthFacade: state.update({loading: true, error: null})
    AuthFacade-->>LoginPage: vm() reactivo → loading=true, botón disabled

    AuthFacade->>AuthHttp: login(email, password)
    AuthHttp->>API: POST /api/auth/login\n{ email, password }
    API-->>AuthHttp: 200 JSON\n{\n  access_token,\n  refresh_token,\n  expires_in,\n  user,\n  roles,\n  menus,\n  catalogs\n}
    AuthHttp-->>AuthFacade: Observable emits LoginResponse

    AuthFacade->>Storage: setItem('access_token', res.access_token)
    AuthFacade->>Storage: setItem('refresh_token', res.refresh_token)
    AuthFacade->>Storage: setItem('auth_user', JSON.stringify(res.user))
    AuthFacade->>Storage: setItem('auth_roles', JSON.stringify(res.roles))
    AuthFacade->>MenuFacade: setMenus(res.menus)
    AuthFacade->>Storage: setItem('catalogs.*', ...)

    AuthFacade->>AuthFacade: state.update({\n  user, token, refreshToken,\n  roles, loading: false,\n  error: null,\n  success: 'Sesión iniciada correctamente'\n})

    AuthFacade-->>LoginPage: vm() reactivo → success message
    AuthFacade->>AuthFacade: setTimeout → router.navigateByUrl('/')
    AuthFacade-->>User: Redirección a welcome
```

## Interceptor 401 — Refresh Automático

```mermaid
sequenceDiagram
    participant App as Angular App
    participant Interceptor as AuthInterceptor
    participant Subject as refreshSubject<br/>(Subject mutex)
    participant AuthHttp as AuthHttpService
    participant AuthFacade as AuthFacade
    participant API as Backend API
    participant Storage as localStorage

    App->>Interceptor: HTTP Request\n(Bearer token)
    Interceptor->>API: GET /api/protected/resource
    API-->>Interceptor: 401 Unauthorized

    Note over Interceptor,Subject: isRefreshing = false (primera vez)
    Interceptor->>Subject: new Subject<string | null>()
    Interceptor->>Interceptor: isRefreshing = true

    Interceptor->>AuthHttp: refresh(refresh_token)
    AuthHttp->>API: POST /api/auth/refresh\n{ refresh_token }
    API-->>AuthHttp: 200 JSON\n{ access_token, refresh_token,\n  expires_in, roles, menus, catalogs }\n(sin user)
    AuthHttp-->>Interceptor: Observable emits

    Interceptor->>Storage: setItem('access_token', res.access_token)
    Interceptor->>Storage: setItem('refresh_token', res.refresh_token)
    Interceptor->>Subject: subject.next(res.access_token)
    Interceptor->>Subject: subject.complete()
    Interceptor->>Interceptor: isRefreshing = false

    Subject->>Interceptor: switchMap → retry original request
    Interceptor->>API: GET /api/protected/resource\n(BEARER new_token)
    API-->>Interceptor: 200 OK
    Interceptor-->>App: Response success

    Note over Interceptor,Subject: Requests concurrentes durante refresh\nse suscriben al mismo Subject\ny esperan take(1) para retry
```

## Fallo del Refresh — Hard Redirect

```mermaid
sequenceDiagram
    participant App as Angular App
    participant Interceptor as AuthInterceptor
    participant Subject as refreshSubject
    participant AuthHttp as AuthHttpService
    participant API as Backend API
    participant Storage as localStorage

    App->>Interceptor: HTTP Request
    Interceptor->>API: GET /api/protected/resource
    API-->>Interceptor: 401 Unauthorized

    Interceptor->>Subject: new Subject()
    Interceptor->>AuthHttp: refresh(refresh_token)
    AuthHttp->>API: POST /api/auth/refresh\n{ refresh_token }
    API-->>AuthHttp: 401 / 400 (refresh expirado)
    AuthHttp-->>Interceptor: error Observable

    Interceptor->>Storage: removeItem('access_token')
    Interceptor->>Storage: removeItem('refresh_token')
    Interceptor->>Storage: removeItem('auth_user')
    Interceptor->>Storage: removeItem('auth_roles')
    Interceptor->>Storage: removeItem('catalogs.*')

    Interceptor->>Subject: subject.next(null)
    Interceptor->>Subject: subject.complete()
    Interceptor->>Interceptor: isRefreshing = false

    Interceptor->>Interceptor: window.location.href = '/login'
    Note over Interceptor: Hard redirect (recarga completa)\nlimpia todo el estado Angular

    Subject->>Interceptor: switchMap → newToken = null
    Interceptor->>App: throwError('Sesión expirada')
```

## Guard de Autenticación

```mermaid
flowchart LR
    User["👤 Usuario\nnavega a ruta protegida"]
    Guard["AuthGuard\ncanActivate()"]
    Auth["AuthFacade\nisAuthenticated()"]
    True["✅ Acceso permitido\n→ carga componente"]
    False["❌ Redirección\n→ /not-logged-in"]

    User --> Guard
    Guard --> Auth
    Auth -->|"token != null"| True
    Auth -->|"token == null"| False
```

**Descripción:** El flujo de autenticación maneja tres escenarios:

1. **Login exitoso**: El `AuthFacade.login()` envía credenciales a `POST /api/auth/login`. Al recibir la respuesta, persiste tokens y datos en `localStorage`, actualiza el menú via `MenuFacade.setMenus()`, y redirige al home.

2. **Refresh automático (Subject mutex)**: Cuando el `AuthInterceptor` recibe un `401`, verifica si ya hay un refresh en curso via la variable `isRefreshing`. Si es el primero, crea un `Subject<string | null>` y llama a `POST /api/auth/refresh`. Las requests concurrentes que también reciben 401 se suscriben al mismo Subject y esperan (`take(1)`) el nuevo token. Cuando el refresh completa, todas las requests en cola se re-ejecutan con el nuevo token via `switchMap`.

3. **Fallo del refresh**: Si el refresh también falla, se limpia todo el `localStorage`, se emite `null` por el Subject (causando error en todas las requests en cola), y se forza un hard redirect a `/login` via `window.location.href`.
