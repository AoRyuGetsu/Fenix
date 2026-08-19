# Analítica API

API de análisis de datos basada en una arquitectura **Modular Service-Oriented MVC** con PHP 8.2+.

## Estructura del proyecto

```
analitica_api/
├── API/             ← Backend (PHP 8.2+, sin framework)
│   ├── app/         ←   Código fuente (Core + Features)
│   ├── config/      ←   Configuración (DI, base de datos)
│   ├── DB/          ←   Scripts SQL y migraciones
│   ├── public/      ←   Entry point (index.php)
│   ├── tests/       ←   PHPUnit
│   ├── var/         ←   Logs, caché
│   ├── vendor/      ←   Dependencias Composer
│   ├── composer.json
│   └── .env
├── CLIENT/          ← Frontend (Angular 22+, Bootstrap 5)
│   ├── src/         ←   Código fuente (standalone components, signals)
│   ├── dist/        ←   Build de producción
│   ├── angular.json
│   └── package.json
├── AGENTS.md        ← Manual de identidad y operaciones para agentes IA
├── AGENT_CONTEXT.md ← Contexto técnico detallado
├── ARCHITECTURE.md  ← Documento arquitectónico
└── README.md        ← Este archivo
```

Ver `ARCHITECTURE.md` para la especificación completa.

Las **migraciones de datos** (roles, usuarios iniciales, etc.) se ejecutan con scripts PHP desde `API/DB/Migrations/`. Ver [`API/DB/Migrations/README.md`](API/DB/Migrations/README.md) para detalles.

## Stack tecnológico

### Backend

| Librería | Propósito |
|---|---|
| `nikic/fast-route` | Enrutamiento |
| `monolog/monolog` | Logs |
| `vlucas/phpdotenv` | Configuración por entorno |
| `php-di/php-di` | Inyección de dependencias |
| `doctrine/orm` | ORM |
| `symfony/validator` | Validación |
| `lcobucci/jwt` | JWT |
| `symfony/error-handler` | Manejo de errores |
| `symfony/http-foundation` | Request/Response |
| `zircote/swagger-php` | Generación de spec OpenAPI |
| `doctrine/orm` SQLFilter | Soft delete automático (`SoftDeleteFilter`) |
| `middleware.php` + `AuthContext` | Autenticación JWT y autorización por roles en rutas |
| `phpunit/phpunit` | Testing |

### Frontend

| Tecnología | Propósito |
|---|---|
| Angular 22+ | SPA con standalone components y signals |
| Bootstrap 5 | Sistema de diseño base |
| Bootstrap Icons | Iconografía |
| TypeScript 6+ | Tipado estricto |
| `app-gtable` | Componente genérico de tabla con paginación, columnas configurables y detalle expandible |

## Inicio rápido

### Requisitos

- PHP >= 8.2
- Composer
- MySQL 8.0+

### Instalación y arranque

```bash
# 1. Clonar e ir al backend
git clone <url-del-repositorio> analitica_api
cd analitica_api/API

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
# Editar .env: credenciales DB, JWT_SECRET, etc.

# 4. Crear base de datos
mysql -u root -p < DB/SQL/001_create_database.sql
mysql -u root -p analiticaDB < DB/SQL/002_create_structure.sql

# 5. Iniciar servidor de desarrollo
composer serve
```

El servidor quedará disponible en `http://localhost:8000`.

### Frontend

```bash
# 1. Ir al frontend
cd CLIENT

# 2. Instalar dependencias
npm install

# 3. Iniciar servidor de desarrollo
ng serve
```

El servidor quedará disponible en `http://localhost:4200`.

### Verificar que funciona

```bash
curl http://localhost:8000/health
# → {"status":"ok","message":"API is running"}
```

### Ejecutar migraciones de datos

Abrir en el navegador (o con curl):

```
http://localhost:8000/migrate.php                → modo incremental
http://localhost:8000/migrate.php?confirm=reset  → borra DB y regenera desde cero
```

También disponible desde el frontend en `http://localhost:4200/migrate`.

En desarrollo se accede sin restricciones. En producción requiere definir `MIGRATION_TOKEN` en `.env` y acceder como `/migrate.php?token=...`.

El modo de ejecución se controla con la variable `RESET_DATABASE_ON_MIGRATE` en el `.env`:
- `false` (default) → incremental: verifica existencias, no pierde datos
- `true` → reset completo: borra todo y regenera estructura + datos

### Credenciales de ingreso

| Usuario | Contraseña |
|---------|-----------|
| `administrador@mail.com` | `adminPass1234` |
| `supervisor@mail.com` | `pass1234` |
| `entrenador@mail.com` | `pass1234` |
| `jugador@mail.com` | `pass1234` |
| `representante@mail.com` | `pass1234` |

### Documentación interactiva

Una vez corriendo, abre en el navegador:

| URL | Descripción |
|-----|-------------|
| `http://localhost:8000/docs` | Swagger UI — explorar y probar endpoints |
| `http://localhost:8000/api-docs` | Spec OpenAPI en JSON |

### Comandos útiles

#### Backend (API/)

```bash
cd API

# Instalar dependencias
composer install

# Actualizar dependencias
composer update

# Iniciar servidor embebido de PHP
composer serve

# Generar spec OpenAPI estático en public/openapi.json
composer openapi

# Ejecutar tests
composer test
```

#### Frontend (CLIENT/)

```bash
cd CLIENT

# Instalar dependencias
npm install

# Iniciar servidor de desarrollo
ng serve

# Build producción
ng build

# Ejecutar tests
ng test
```

## Despliegue

### CORS

Los headers CORS se configuran en `API/app/Core/Kernel.php`, método `handle()`:

```php
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
```

La URL base del backend se obtiene de `CLIENT/public/config/config.json` (campo `apiUrl`) a través de `ConfigService`. En desarrollo típicamente `http://localhost:8000`, en producción puede ser el mismo origen o un dominio separado.

Para producción, cambiar `Access-Control-Allow-Origin` de `*` al dominio específico del frontend. Si están en el mismo origen, no se requieren CORS.

### Configuración de la app

Toda la configuración que el frontend necesita conocer (apiUrl, logoUrl, backgroundUrl) se define en `CLIENT/public/config/config.json` y se carga via `ConfigService` (`APP_INITIALIZER`). No existen archivos `environments/` en el frontend.

---

## Autenticación y Autorización

El sistema usa **JWT (HS256)** con middleware centralizado:

### Middleware de rutas

Todas las rutas protegidas se envuelven con `middleware(Container, handler, roles?)`:

| Tipo | Ejemplo |
|---|---|
| Pública (sin middleware) | `/health`, `/config`, `/auth/login`, `/people` (GET) |
| Autenticada (cualquier rol) | `/auth/me`, GETs de listados, `/users` |
| Solo administrador | POST/PUT/DELETE de cualquier recurso |

**Flujo del middleware:**
1. Extrae `Authorization: Bearer <token>` del header
2. Valida el token JWT
3. Crea `AuthContext` (userId, email, roles, permissions) y lo inyecta en el Container
4. Si la ruta requiere roles específicos, verifica que el usuario tenga al menos uno (403 en caso contrario)
5. Si el usuario es administrador, desactiva `SoftDeleteFilter` para que pueda ver registros soft-deleteados
6. Ejecuta el controller

### Soft Delete automático

El `SoftDeleteFilter` (Doctrine SQLFilter) agrega automáticamente `deletedAt IS NULL` a todas las consultas. Los administradores lo ven desactivado (ven registros eliminados).

### Endpoints públicos y protegidos

Ver `ARCHITECTURE.md` §2.13 para la tabla completa de endpoints con sus requisitos de autenticación.

### Features disponibles

| Feature | Backend | Frontend |
|---------|---------|----------|
| Usuarios (CRUD + admin) | `app/Features/User/` | `CLIENT/src/app/features/users/` |
| Roles y Permisos | `app/Features/RolesPermisos/` | `core/auth/` (RolesPermisosPage) |
| Personas | `app/Features/People/` | `features/users/` (tabs por tipo de persona) |
| Ficha de Jugador | `app/Features/PlayerSpecification/` | `features/users/` (PlayerSpecModal) + `features/evaluations/` (PlayerInfoModal) |
| Representantes | `app/Features/Representatives/` | `features/users/` (asociación en alta/edición de jugador) + `features/representados/` (self-service REP) |
| Parentescos | `app/Features/Parentescos/` | `features/users/` (select en modales de jugador, vía `GET /parentescos`) |
| Cuestionarios | `app/Features/Questionnaire/` | `features/questionnaire/` |
| Evaluaciones | `app/Features/Questionnaire/` (Evaluation + Answer) | `features/evaluations/` |
| Estadísticas | `app/Features/EvaluationStats/` | (pendiente) |
