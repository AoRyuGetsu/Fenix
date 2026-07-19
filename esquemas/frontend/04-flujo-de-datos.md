# Flujo de Datos (Data Flow)

Diagramas de secuencia que muestran cómo fluyen los datos desde la interacción del usuario hasta la respuesta del backend y la actualización reactiva de la UI mediante señales.

## Flujo Principal: Carga de Datos (ngOnInit)

```mermaid
sequenceDiagram
    actor User as 👤 Usuario
    participant Page as PageComponent<br/>(UsersPage)
    participant Facade as UsersFacade<br/>(signals + vm())
    participant Service as UsersHttpService
    participant API as Backend API<br/>(PHP)

    Note over User,API: 1. El usuario navega a la ruta

    Page->>Page: ngOnInit()
    Page->>Facade: loadTabs()

    Facade->>Facade: state.update({loading: true})
    Facade-->>Page: vm() reactivo → UI muestra spinner

    Facade->>Service: getPeopleTypes()
    Service->>API: GET /api/people-types
    API-->>Service: 200 JSON { data: [...], total, page, perPage }
    Service-->>Facade: Observable emits response

    Facade->>Facade: state.update({\n  tabs: types,\n  loading: false,\n  activeTab: firstTab\n})
    Facade->>Facade: loadTabData(activeTab)

    Facade->>Facade: state.update({loading: true})
    Facade->>Service: getPeople(type, page, perPage)
    Service->>API: GET /api/people?type=...&page=1&perPage=20
    API-->>Service: 200 JSON { data: [...], total: 50, page: 1, perPage: 20 }
    Service-->>Facade: Observable emits response

    Facade->>Facade: state.update({\n  people: { [tab]: res },\n  loading: false,\n  activePage: 1,\n  totalPages: 3\n})

    Facade-->>Page: vm() reactivo → data actualizados
    Page->>Page: computed() re-evalúa currentItems, currentTotal
    Page->>User: Template renderiza tabla con datos
```

## Flujo de Paginación

```mermaid
sequenceDiagram
    actor User as 👤 Usuario
    participant Page as UsersPageComponent
    participant GTable as GTableComponent<br/>(app-gtable)
    participant Facade as UsersFacade
    participant Service as UsersHttpService
    participant API as Backend API

    User->>GTable: Click en página 2
    GTable->>GTable: pageChange.emit(2)
    GTable-->>Page: @Output() pageChange = 2

    Page->>Page: onPageChange(2)
    Page->>Facade: changePage(2)

    Facade->>Facade: state.update({loading: true})
    Facade-->>Page: vm() reactivo → loading=true

    Facade->>Service: getPeople(type, 2, 20)
    Service->>API: GET /api/people?type=...&page=2&perPage=20
    API-->>Service: 200 JSON { data: [...], total: 50, page: 2, perPage: 20 }
    Service-->>Facade: Observable emits

    Facade->>Facade: state.update({\n  people: { [tab]: res },\n  loading: false,\n  activePage: 2\n})

    Facade-->>Page: vm() reactivo → data página 2
    Page->>Page: computed() re-evalúa currentItems
    Page-->>GTable: [data]="currentItems()" actualizado
    GTable->>User: Renderiza filas de página 2
```

## Flujo de Error

```mermaid
sequenceDiagram
    participant Facade as UsersFacade
    participant Service as UsersHttpService
    participant API as Backend API
    participant ErrUtil as getErrorMessage()

    Facade->>Service: getPeople(type, page, perPage)
    Service->>API: GET /api/people
    API-->>Service: 500 Error HTTP
    Service-->>Facade: error Observable

    Facade->>ErrUtil: getErrorMessage(err, 'Fallback msg')
    ErrUtil-->>Facade: "Error interno del servidor"

    Facade->>Facade: state.update({\n  loading: false,\n  error: "Error interno del servidor"\n})

    Note over Facade: vm() reactivo → template muestra alerta de error
```

**Descripción:** El flujo de datos sigue un patrón unidireccional consistente:

1. **Inicio**: El componente llama a un método del Facade desde `ngOnInit()` o por acción del usuario.
2. **Loading**: El Facade actualiza el estado con `{loading: true}`, el `vm()` reactivo propaga el cambio al template que muestra un spinner.
3. **HTTP**: El Facade llama al servicio HTTP, que hace la petición a la API.
4. **Respuesta**: El servicio recibe la respuesta y la emite via Observable.
5. **Estado**: El Facade actualiza el estado con los datos y `{loading: false}` usando `state.update(s => ({...s, data, loading: false}))`.
6. **Re-render**: El `vm()` (readonly signal) propaga el cambio reactivamente. Los `computed()` en el componente se re-evalúan. El template se actualiza automáticamente.
7. **Error**: En caso de error, se usa `getErrorMessage(err, fallback)` para obtener el mensaje y se actualiza el estado con `{error: mensaje}`.
