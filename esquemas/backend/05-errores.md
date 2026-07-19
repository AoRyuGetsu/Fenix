# 05 — Manejo de Errores

Diagrama de flujo que muestra cómo se manejan los errores en el backend, desde que un Service lanza una excepción hasta que se devuelve una respuesta JSON estructurada al cliente.

```mermaid
flowchart TD
    START["Service detecta error
    (ej: entidad no encontrada, validación fallida)"] --> THROW["throw new AppException('ERROR_CODE')
    Ej: AppException('USER_NOT_FOUND')"]

    THROW --> APP_EXCEPTION["AppException::__construct(code)"]
    APP_EXCEPTION --> GET_ERROR["Llama a getError(code)
    (función global en helpers.php)"]

    GET_ERROR --> STATIC_CACHE{"Static Cache
    ¿errors cargado?"}
    STATIC_CACHE -- "No" --> REQUIRE["require config/errors.php
    (una vez por request)"]
    REQUIRE --> RETURN_MSG["Retorna string mensaje
    Ej: 'Usuario no encontrado'"]
    STATIC_CACHE -- "Sí" --> RETURN_MSG

    GET_ERROR --> INFER_STATUS["AppException::inferHttpStatus(code)"]
    INFER_STATUS --> MATCH_STATUS{"Patrón del código"}
    MATCH_STATUS -- "*_NOT_FOUND" --> STATUS_404["HTTP 404"]
    MATCH_STATUS -- "INVALID_CREDENTIALS / TOKEN_*" --> STATUS_401["HTTP 401"]
    MATCH_STATUS -- "USER_DEACTIVATED / FORBIDDEN" --> STATUS_403["HTTP 403"]
    MATCH_STATUS -- "*_EXISTS / *_OVERLAP / CONFLICT" --> STATUS_409["HTTP 409"]
    MATCH_STATUS -- "VALIDATION_*" --> STATUS_422["HTTP 422"]
    MATCH_STATUS -- "VALIDATION_JSON_INVALID" --> STATUS_400["HTTP 400"]
    MATCH_STATUS -- "Otro" --> STATUS_DEFAULT["HTTP 400 (default)"]

    RETURN_MSG --> CONSTRUCT_PARENT["parent::__construct(
        mensaje, httpStatus, previous
    )"]

    CONSTRUCT_PARENT --> PROPAGATE["Se propaga al Controller
    (catch en método del controller)"]

    PROPAGATE --> CATCH["catch (AppException $e)"]
    CATCH --> BUILD_RESPONSE["return new JsonResponse([
        'error' => $e->getMessage(),
        'code'  => $e->getErrorCode()
    ], $e->getCode())"]

    BUILD_RESPONSE --> JSON_RESPONSE["Respuesta JSON al cliente
    {
        'error': 'Usuario no encontrado',
        'code': 'USER_NOT_FOUND'
    }"]

    %% Flujo alternativo: Validación/Infraestructura
    CONTROLLER_ERROR["Controller detecta error de validación
    (sin try-catch de Service)"] --> DIRECT_GET_ERROR["return getError('VALIDATION_JSON_INVALID')"]
    DIRECT_GET_ERROR --> DIRECT_RESPONSE["return new JsonResponse([
        'error' => getError('CODE'),
        'code' => 'CODE'
    ], status)"]

    DIRECT_RESPONSE --> JSON_RESPONSE

    %% Error no capturado
    UNCAUGHT["Excepción no capturada
    (Throwable en Kernel)"] --> KERNEL_CATCH["Kernel::handle()
    catch (\Throwable)"]
    KERNEL_CATCH --> ERROR_500["JsonResponse
    {'error': 'Internal server error'}
    HTTP 500"]

    style THROW fill:#ffcccc,stroke:#cc0000
    style APP_EXCEPTION fill:#ffe0cc,stroke:#cc6600
    style GET_ERROR fill:#ffffcc,stroke:#cccc00
    style CATCH fill:#ccffcc,stroke:#00cc00
    style BUILD_RESPONSE fill:#ccffcc,stroke:#00cc00
    style JSON_RESPONSE fill:#cce5ff,stroke:#0066cc
    style KERNEL_CATCH fill:#ffcccc,stroke:#cc0000
    style ERROR_500 fill:#ffcccc,stroke:#cc0000
```

## Reglas Clave

| Regla | Descripción |
|---|---|
| **No hardcodear mensajes** | Todos los mensajes viven en `config/errors.php` |
| **AppException en Services** | Services lanzan `throw new AppException('CODE')` |
| **getError() en Controllers** | Para errores de validación/infra sin try-catch |
| **Siempre incluir `code`** | La respuesta JSON debe tener `error` + `code` |
| **Nunca interpolar IDs** | Prohibido: `"Usuario #{$id} no encontrado"` |

## Mapeo de Códigos a HTTP Status

| Patrón | HTTP Status | Ejemplos |
|---|---|---|
| `*_NOT_FOUND` | 404 | `USER_NOT_FOUND`, `QUESTION_NOT_FOUND` |
| `INVALID_CREDENTIALS`, `TOKEN_*` | 401 | `TOKEN_EXPIRED`, `TOKEN_INVALID` |
| `USER_DEACTIVATED`, `FORBIDDEN` | 403 | — |
| `*_EXISTS`, `*_OVERLAP`, `CONFLICT` | 409 | `EMAIL_ALREADY_EXISTS` |
| `VALIDATION_*` (mayoría) | 422 | `VALIDATION_REQUIRED` |
| `VALIDATION_JSON_INVALID` | 400 | — |
| `METHOD_NOT_ALLOWED` | 405 | — |
| `INTERNAL_ERROR` | 500 | — |
