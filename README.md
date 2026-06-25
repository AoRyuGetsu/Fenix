# Analítica API

API de análisis de datos basada en una arquitectura **Modular Service-Oriented MVC** con PHP 8.3+.

## Estructura del proyecto

```
analitica_api/
├── API/             ← Backend (PHP 8.3+, sin framework)
│   ├── app/         ←   Código fuente (Core + Features)
│   ├── config/      ←   Configuración (DI, base de datos)
│   ├── DB/          ←   Scripts SQL y migraciones
│   ├── public/      ←   Entry point (index.php)
│   ├── tests/       ←   PHPUnit
│   ├── var/         ←   Logs, caché
│   ├── vendor/      ←   Dependencias Composer
│   ├── composer.json
│   └── .env
├── CLIENT/          ← Frontend (Angular 19+, Bootstrap 5)
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
| `phpunit/phpunit` | Testing |

### Frontend

| Tecnología | Propósito |
|---|---|
| Angular 19+ | SPA con standalone components y signals |
| Bootstrap 5 | Sistema de diseño base |
| Bootstrap Icons | Iconografía |
| TypeScript 5+ | Tipado estricto |
| Shared form controls | Componentes genéricos para formularios (text, number, select, date, textarea) |

## Inicio rápido

### Requisitos

- PHP >= 8.3
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
http://localhost:8000/migrate.php
```

También disponible desde el frontend en `http://localhost:4200/migrate`.

En desarrollo se accede sin restricciones. En producción requiere definir `MIGRATION_TOKEN` en `.env` y acceder como `/migrate.php?token=...`.

### Credenciales de ingreso

| Usuario | Contraseña |
|---------|-----------|
| `administrador@mail.com` | `adminPass1234` |

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

Si frontend y backend sirven desde el **mismo dominio** (ej: backend sirve los archivos estáticos de Angular en `public/`), la URL base se resuelve automáticamente como `''` en producción y `http://localhost:8000` en desarrollo vía `isDevMode()`.

Para producción, cambiar `Access-Control-Allow-Origin` de `*` al dominio específico del frontend. Si están en el mismo origen, no se requieren CORS.

### Configuración de la app

Toda la configuración que el frontend necesita conocer (nombre de la app, URL de la API, etc.) se define en el `.env` del backend, se expone en `GET /config` y se consume via `ConfigFacade`. No existen archivos `environments/` en el frontend.
