# Capas de la Aplicación Frontend

Diagrama de las tres capas principales (Core, Shared, Features) con sus componentes internos y un ejemplo expandido de un Feature.

```mermaid
flowchart TB
    subgraph Core ["🟦 CORE"]
        direction TB
        Auth["Auth"]
        AuthSub["├─ AuthFacade\n│  └─ vm() state signal\n├─ AuthHttpService\n├─ AuthGuard\n├─ AuthRedirectGuard\n└─ PermissionGuard"]
        Menu["Menu"]
        MenuSub["├─ MenuFacade\n│  └─ menuTree computed\n│  └─ flatMenus signal"]
        Config["Config"]
        ConfigSub["└─ ConfigService\n   └─ load() APP_INITIALIZER\n   └─ apiUrl / logoUrl"]
        Interceptors["Interceptors"]
        InterceptorsSub["└─ authInterceptor\n   ├─ JWT injection\n   └─ 401 Subject mutex\n      refresh / retry"]
        Layout["Layout"]
        LayoutSub["├─ AppShellComponent\n│  └─ RouterOutlet\n└─ SidebarComponent\n   └─ MenuFacade.menuTree"]
    end

    subgraph Shared ["🟩 SHARED"]
        direction TB
        GTable["GTableComponent\n├─ app-gtable selector\n├─ GtColumn / GtAction\n├─ GtPagination\n├─ GtDetailConfig\n└─ pageChange / actionClick"]
        Utils["Utils\n└─ getErrorMessage()\n   ├─ err.status 401\n   ├─ err.error.error\n   └─ fallback string"]
        Types["Types\n└─ gtable.types.ts\n   ├─ GtColumn<T>\n   ├─ GtAction<T>\n   ├─ GtPagination\n   └─ GtDetailConfig"]
    end

    subgraph Features ["🟧 FEATURES"]
        direction TB
        Users["Users"]
        Periods["Periods"]
        Evaluations["Evaluations"]
        Questionnaire["Questionnaire"]

        subgraph UsersExpanded ["┄┄┄ Ejemplo expandido: Users ┄┄┄┄┄┄┄┄┄┄"]
            direction TB
            URoutes["routes.ts\n└─ lazy-loaded route"]
            UPages["pages/\n└─ UsersPageComponent"]
            UComponents["components/\n├─ UsersTableComponent\n├─ PersonCreateModal\n├─ PersonUpdateModal\n├─ AdminCreateModal\n└─ AdminUpdateModal"]
            UServices["services/\n└─ UsersHttpService"]
            UFacades["facades/\n└─ UsersFacade\n   ├─ state signal<UsersState>\n   ├─ vm = state.asReadonly()\n   ├─ loadTabs()\n   ├─ changePage()\n   ├─ createPerson()\n   └─ deleteUser()"]
        end
    end

    Core -->|"usado por"| Features
    Shared -->|"usado por"| Features
    Core -->|"usado por"| Shared

    style Core fill:#e3f2fd,stroke:#1565c0,stroke-width:2px
    style Shared fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    style Features fill:#fff3e0,stroke:#e65100,stroke-width:2px
    style UsersExpanded fill:#fff8e1,stroke:#ff8f00,stroke-width:1px,stroke-dasharray: 5 5
```

**Descripción:** La aplicación Angular se divide en tres capas. **Core** maneja autenticación, menú dinámico, configuración, interceptores HTTP y el layout general (app-shell con sidebar). **Shared** contiene componentes reutilizables (GTable para tablas paginadas) y utilidades (getErrorMessage). Cada **Feature** es un módulo lazy-loaded con su propia estructura interna (routes → pages → components → services → facades). Las Features se comunican solo a través de Core (nunca directamente entre sí). El Core se carga al inicio, mientras que cada Feature se carga bajo demanda por ruta.
