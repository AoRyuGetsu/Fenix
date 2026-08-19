# 02 — Arquitectura en Capas del Backend

Diagrama de la arquitectura hexagonal simplificada del backend. Se distinguen tres capas principales (Presentación, Aplicación, Dominio) más la capa de Infraestructura transversal. Las flechas indican dependencias: las capas exteriores dependen de las interiores, nunca al revés.

```mermaid
flowchart TB
    subgraph PRESENTACION["Capa de Presentación"]
        ROUTES["Routes (FastRoute)
        routes.php auto-descubiertos"]
        MIDDLEWARE["Middleware
        JWT Validation + Role Check
        SoftDeleteFilter bypass"]
        CONTROLLERS["Controllers
        ControllerInterface
        execute(): Response"]
    end

    subgraph APLICACION["Capa de Aplicación"]
        SERVICES["Services
        Lógica de negocio
        Orquestación de repositorios
        AuthContext inyectado (userId/roles)"]
        DTOS["DTOs
        Query DTOs · Response DTOs
        Validación via Symfony Validator"]
    end

    subgraph DOMINIO["Capa de Dominio"]
        ENTITIES["Entities
        Doctrine ORM (atributos #[ORM])
        Mapeo a MySQL"]
        INTERFACES["Interfaces
        Contratos Service + Repository
        PSR-4"]
        REPOSITORIES["Repositories
        EntityManager
        findPaginated(Query DTO)"]
    end

    subgraph INFRAESTRUCTURA["Infraestructura Transversal"]
        DI["DI Container (PHP-DI 7)
        Autowiring + config/di.php"]
        KERNEL["Kernel
        Entrypoint · Registro de rutas
        Manejo de CORS"]
        AUTH["Auth (lcobucci/jwt 5)
        HS256 · Access/Refresh tokens
        AuthContext DTO"]
        LOGGER["Logger (Monolog 3)
        RotatingFileHandler
        var/log/app.log"]
        VALIDATOR["Validator (Symfony 7)
        #[Assert\\...] attributes"]
        ERROR["Error Handler
        AppException + config/errors.php
        getError() global"]
    end

    ROUTES --> KERNEL
    MIDDLEWARE --> AUTH
    CONTROLLERS --> MIDDLEWARE
    CONTROLLERS --> SERVICES
    SERVICES --> INTERFACES
    INTERFACES --> REPOSITORIES
    REPOSITORIES --> ENTITIES
    SERVICES --> DTOS
    DI --> KERNEL
    DI --> CONTROLLERS
    DI --> SERVICES
    DI --> REPOSITORIES
```

## Reglas de Dependencia

| Dirección | Permiso | Prohibición |
|---|---|---|
| Controller → Service | Via interfaz (inyectada en constructor) | Lógica de negocio en controller |
| Service → Repository | Via interfaz (inyectada en constructor) | Instanciar repositorios con `new` |
| Repository → EntityManager | Inyección directa (Doctrine) | Queries SQL nativas fuera de DQL |
| Capa interna | Depende de interfaces del dominio | Importar implementaciones concretas de otros features |
