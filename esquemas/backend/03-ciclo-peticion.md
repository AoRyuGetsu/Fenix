# 03 — Ciclo de Vida de una Petición

Diagrama de secuencia que muestra el flujo completo de una petición HTTP desde que ingresa hasta que se devuelve la respuesta, incluyendo todos los componentes intermedios: Kernel, Router, Middleware, Controller, Service, Repository y base de datos.

```mermaid
sequenceDiagram
    participant C as Cliente (Angular)
    participant I as public/index.php
    participant K as Kernel
    participant R as Router (FastRoute)
    participant M as Middleware
    participant CT as Controller
    participant S as Service
    participant RP as Repository
    participant DB as MySQL

    C->>I: HTTP Request
    I->>I: 1. Carga autoload (Composer)
    I->>I: 2. Carga Env (.env)
    I->>I: 3. Registra ErrorHandler (Symfony)
    I->>I: 4. Crea Request desde globals
    I->>K: 5. Kernel::handle(Request)

    K->>K: 6. Container::__construct()
    K->>K: 7. Carga config/di.php (PHP-DI)
    K->>K: 8. Router::__construct()
    K->>K: 9. registerFromFeatures()
    Note over K: Escanea Features/*/Routes/routes.php<br>e Infrastructure/*/Routes/routes.php
    K->>R: 10. $router->dispatch($request)
    K->>K: 11. $container->set(Request::class, $request)

    R->>R: 12. FastRoute simpleDispatcher()
    R->>R: 13. Dispatcher::dispatch(method, uri)
    alt Ruta no encontrada
        R-->>C: 404 {"error": "Route not found"}
    else Método no permitido
        R-->>C: 405 {"error": "Method not allowed"}
    else Ruta encontrada
        R->>M: 14. Resuelve handler (closure)
    end

    alt Endpoint público (sin middleware)
        Note over M: /health, /config, /auth/login, /auth/refresh,<br>/people-types, /parentescos, /people, /people/{id},<br>/periods (GET)
    else Endpoint protegido
        M->>M: 15. Extrae Bearer token
        M->>M: 16. AuthService::validateToken()
        M->>M: 17. Verifica firma HS256 + expiración

        alt Token inválido/expirado
            M-->>C: 401 {"error": "TOKEN_INVALID/EXPIRED", "code": "..."}
        end

        M->>M: 18. Crea AuthContext (userId, email, roles, permissions)
        K->>M: 19. $container->set(AuthContext::class)

        alt Roles requeridos (ej: ['ADM'])
            M->>M: 20. Verifica hasRole()
            alt Sin rol requerido
                M-->>C: 403 {"error": "Forbidden"}
            end
        end

        alt Usuario es administrador
            M->>M: 21. Desactiva SoftDeleteFilter
            Note over M: Permite ver registros<br>soft-deleteados
        end
    end

    M->>CT: 22. Controller::execute() o método específico
    CT->>CT: 23. json_decode($request->getContent())
    CT->>CT: 24. Extrae query params (page, perPage, filtros:
        search, team_id, questionnaire_id, period_ids)

    alt Error de validación
        CT-->>C: 400/422 {"error": "VALIDATION_*", "code": "..."}
    end

    CT->>S: 25. Service::método(data)

    alt Error de negocio
        S->>S: Lanza AppException('ERROR_CODE')
        S-->>CT: AppException
        CT-->>C: {"error": getError('CODE'), "code": "CODE"}
    end

    S->>S: 26. Lógica de negocio
    S->>RP: 27. Repository::findPaginated(QueryDTO)
    RP->>DB: 28. Doctrine DQL (con SQLFilter)
    DB-->>RP: 29. Resultados
    RP-->>S: 30. [data, total]
    S-->>CT: 31. Resultado procesado

    CT-->>C: 32. JsonResponse {data, total, page, perPage}
    Note over C: Respuesta paginada

    C->>C: 33. Renderiza en Angular
```

## Resumen del Flujo

| Paso | Componente | Acción |
|---|---|---|
| 1-5 | `public/index.php` | Bootstrap: autoload, Env, ErrorHandler, Request |
| 6-11 | `Kernel` | Construye Container + Router, descubre rutas |
| 12-14 | `Router` (FastRoute) | Match de método + URI, resuelve handler |
| 15-21 | `Middleware` | Valida JWT, verifica roles, inyecta AuthContext |
| 22-24 | `Controller` | Parsea JSON, extrae params, delega a Service |
| 25-31 | `Service → Repository → DB` | Lógica de negocio, consultas Doctrine |
| 32-33 | `Controller → Cliente` | JsonResponse paginado |
