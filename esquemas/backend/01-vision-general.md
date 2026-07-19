# 01 — Visión General del Monorepo

Diagrama de alto nivel que muestra los tres componentes principales del sistema: el backend PHP, el frontend Angular y la base de datos MySQL, junto con los protocolos de comunicación entre ellos.

```mermaid
flowchart TB
    subgraph CLIENT["CLIENT / Frontend (Angular 22+)"]
        ANGULAR["SPA Angular
        Signals · Standalone
        Bootstrap 5"]
    end

    subgraph API["API / Backend (PHP 8.2+)"]
        KERNEL["Kernel (Framework propio)"]
        FEATURES["Features
        User · People · Questionnaire
        RolesPermisos · Evaluations"]
        INFRA["Infrastructure
        Auth/JWT · Health · Config · Menu"]
        CORE["Core
        Router · Container · Env
        OpenAPI · ErrorHandler"]
    end

    subgraph DB["Base de Datos (MySQL 8)"]
        MYSQL[("MySQL 8+
        utf8mb4")]
    end

    CLIENT -- "HTTP REST (JSON)" --> API
    CLIENT -- "config.json (estático)" --> CLIENT
    API -- "Doctrine 3 ORM" --> DB
    API -- "Monolog" --> LOG[(var/log/app.log)]
```

## Componentes

| Componente | Tecnología | Propósito |
|---|---|---|
| **API** | PHP 8.2+, sin framework | Backend REST monolítico con organización modular por features |
| **CLIENT** | Angular 22+, Bootstrap 5 | SPA standalone con signals, lazy-loading por feature |
| **DB** | MySQL 8.0+ | Almacenamiento persistente con `utf8mb4` |
| **Logs** | Monolog 3 | Rotación diaria en `var/log/app.log` |

## Comunicación

- **API ↔ DB**: Doctrine 3 ORM con atributos PHP 8 y `UnderscoreNamingStrategy`
- **CLIENT ↔ API**: HTTP REST (JSON), protegido con JWT Bearer tokens
- **CLIENT ↔ Config**: `public/config/config.json` servido estáticamente
