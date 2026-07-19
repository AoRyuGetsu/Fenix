# Estructura Interna de un Feature

Diagrama detallado de la estructura interna de un Feature (ejemplo: Users), mostrando la relación entre rutas, páginas, componentes, servicios y facades con el patrón `vm()`.

```mermaid
flowchart TB
    subgraph AppRoutes ["app.routes.ts"]
        LazyRoute["path: 'configuracion/usuarios'\nloadChildren: () => import(...)"]
    end

    subgraph Feature ["Feature: Users"]
        direction TB

        Routes["routes.ts\n└─ export default\n   [{ path: '', component: UsersPageComponent }]"]
        Routes -->|"lazy-load"| Page

        subgraph Page ["pages/ · UsersPageComponent"]
            PDescription["@Component({ templateUrl })\nimplements OnInit"]
            PInject["facade = inject(UsersFacade)"]
            PVm["vm = this.facade.vm  ← readonly signal"]
            PComputed["computed() ⤵\ncurrentItems, currentTotal\nselectedPersonItem, selectedAdminItem\npeopleTypes, isPersonTab"]

            PInject --> PVm
            PVm --> PComputed
        end

        subgraph Components ["components/ (presentación pura)"]
            direction TB
            CT1["UsersTableComponent\n├─ @Input() data\n├─ @Input() pagination\n├─ @Output() pageChange\n├─ @Output() delete\n└─ usa app-gtable interno"]
            CT2["PersonCreateModalComponent\n├─ @Input() peopleTypes\n├─ @Output() create\n└─ template Bootstrap modal"]
            CT3["PersonUpdateModalComponent\n├─ @Input() item\n├─ @Output() update\n└─ template Bootstrap modal"]
            CT4["AdminCreateModalComponent\n├─ @Input() show\n├─ @Output() create\n└─ template Bootstrap modal"]
            CT5["AdminUpdateModalComponent\n├─ @Input() item\n├─ @Output() update\n└─ template Bootstrap modal"]
        end

        subgraph Services ["services/"]
            Serv["UsersHttpService\n├─ inject(HttpClient)\n├─ inject(ConfigService)\n├─ getPeopleTypes()\n├─ getPeople()\n├─ getUsers()\n├─ createUser()\n├─ updateUser()\n├─ deleteUser()\n└─ restoreUser()"]
        end

        subgraph Facade ["facades/ · UsersFacade"]
            FDescription["@Injectable({ providedIn: 'root' })"]
            FState["state = signal<UsersState>({\n  tabs, activeTab, people, admins,\n  loading, error, success,\n  showCreateModal, showUpdateModal,\n  selectedItem, activePage, totalPages,\n  searchTerm, showDeleted\n})"]
            FVm["vm = this.state.asReadonly()"]
            FMethods["Métodos:\n├─ loadTabs()\n├─ switchTab()\n├─ setSearchTerm()\n├─ changePage()\n├─ openCreateModal()\n├─ openUpdateModal()\n├─ closeModals()\n├─ createPerson()\n├─ createAdmin()\n├─ updateUser()\n├─ updateAdmin()\n├─ deleteUser()\n├─ restoreUser()\n└─ toggleShowDeleted()"]
            FPattern["Patrón:\n1. state.update(s => ({...s, loading: true}))\n2. api.method().subscribe()\n3. next: state.update(...)\n4. error: state.update({error: getErrorMessage(...)})"]

            FState --> FVm
        end

        Page -->|"llama métodos"| Facade
        Page -->|"pasa datos via @Input"| Components
        Components -->|"emite eventos via @Output"| Page
        Facade -->|"inyecta y llama"| Services
    end

    subgraph TemplatePattern ["Patrón vm() en el template"]
        direction TB
        T1["<!-- El template SOLO accede a vm().prop -->"]
        T2["<app-users-table\n  [data]=\"currentItems()\"\n  [pagination]=\"{ page: vm().activePage, ... }\"\n  (pageChange)=\"onPageChange($event)\"\n  (delete)=\"onDelete($event)\">"]
    end

    Feature --> TemplatePattern

    style AppRoutes fill:#f3e5f5,stroke:#7b1fa2
    style Feature fill:#fff3e0,stroke:#e65100
    style Page fill:#fff8e1,stroke:#ff8f00
    style Components fill:#fff8e1,stroke:#ff8f00
    style Services fill:#fff8e1,stroke:#ff8f00
    style Facade fill:#fff8e1,stroke:#ff8f00
    style TemplatePattern fill:#e8f5e9,stroke:#2e7d32
```

**Descripción:** Cada Feature sigue una estructura estricta de 5 capas:

1. **`routes.ts`** — Define la ruta lazy-loaded que apunta al componente página.
2. **`pages/` (sufijo `Page`)** — Componente página que inyecta el Facade, expone `vm()` y orquesta la comunicación entre componentes hijos. Usa `computed()` para derivar datos de `vm()`.
3. **`components/`** — Componentes de presentación pura: sin lógica de negocio, sin estado propio, sin HTTP. Reciben datos via `@Input()` y emiten eventos via `@Output()`.
4. **`services/`** — Servicios HTTP que encapsulan llamadas `HttpClient` a la API. Usan `ConfigService.apiUrl` para la URL base.
5. **`facades/`** — Manejan todo el estado con señales (`signal`). Exponen `vm()` como `readonly signal` que es la única fuente de datos para el template. El patrón es: `state.update()` → llamar API → en `next`: `state.update()` con datos → en `error`: `state.update()` con `getErrorMessage()`.
