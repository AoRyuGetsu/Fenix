# Analítica — Frontend

SPA en **Angular 22+** (standalone components, signals) con **Bootstrap 5** y paleta Fénix.

## Requisitos

- Node.js >= 20
- Angular CLI >= 22

## Desarrollo

```bash
ng serve          # http://localhost:4200
ng build          # producción → dist/
ng test           # pruebas unitarias (Vitest)
```

## Estructura

```
src/app/
├── core/             ← Estado global (auth, menu, config, layout, guards, interceptors)
│   ├── auth/         ← Login, JWT, guards, facades, HTTP
│   ├── config/       ← ConfigService (apiUrl, logoUrl, backgroundUrl)
│   ├── menu/         ← Menú dinámico desde API
│   ├── layout/       ← AppShell, Sidebar
│   ├── interceptors/ ← AuthInterceptor (JWT + refresh)
│   └── pages/        ← Welcome, Migrate, 404
├── shared/
│   ├── components/gtable/  ← Tabla genérica reutilizable (app-gtable)
│   └── utils/              ← error-messages.ts
├── features/
│   ├── users/              ← CRUD de personas, administradores y fichas de jugador
│   │   ├── routes.ts
│   │   ├── pages/
│   │   ├── components/     ← Tablas, modales, PlayerSpecModalComponent
│   │   ├── services/       ← HTTP (incluye CRUD de player specs)
│   │   └── facades/        ← Estado (signals + vm, player specs)
│   ├── questionnaire/      ← Cuestionarios y preguntas
│   │   ├── routes.ts
│   │   ├── pages/
│   │   ├── components/     ← Tablas, modales, question manager
│   │   ├── services/       ← HTTP
│   │   └── facades/        ← Estado (signals + vm)
│   └── evaluations/        ← Evaluaciones (llenar cuestionarios + ficha del jugador)
│       ├── routes.ts
│       ├── pages/          ← EvaluationsPage, EvaluationFillPage
│       ├── components/     ← EvaluationsTable, QuestionCard, PlayerInfoModalComponent
│       ├── services/       ← HTTP (incluye getPlayerSpec)
│       └── facades/        ← Estado (signals + vm)
  ├── app.routes.ts   ← Rutas raíz con lazy loading
└── main.ts         ← Entrypoint (bootstrapApplication)
```

## Patrones Arquitectónicos

### Componentes de presentación pura

Los componentes NO contienen lógica de negocio. Inyectan la facade de su feature y exponen `vm()`:

```typescript
@Component({ templateUrl: './my-component.html' })
export class MyComponent {
  facade = inject(MyFacade);
  vm = this.facade.vm;
}
```

### Facades con signals

Toda la lógica de negocio y estado vive en la facade. Expone `vm()` como `readonly signal`:

```typescript
@Injectable({ providedIn: 'root' })
export class MyFacade {
  private state = signal<MyState>({ ... });
  vm = this.state.asReadonly();
}
```

### Templates en archivos separados

Todo componente usa `templateUrl` apuntando a un archivo `.html` separado. No se permiten templates inline (excepción: `<ng-template>` reutilizables en tablas/detalles).

### Tablas con app-gtable

Todas las tablas de listado usan el componente genérico `app-gtable` con columnas configurables, paginación, acciones y detalle expandible:

```html
<app-gtable [columns]="cols" [data]="items" [pagination]="pagination()"
  (pageChange)="facade.changePage($event)"></app-gtable>
```

### Formularios con inputs nativos

Todos los formularios usan inputs HTML nativos (`<input>`, `<select>`, `<textarea>`) con clases Bootstrap y signals:

```html
<input type="text" class="form-control" [value]="mySignal()" (input)="mySignal.set($event.target.value)" />
```

Excepción: `LoginPage` usa ReactiveFormsModule.

## Autenticación

- Login via `AuthFacade.login()` → API JWT
- Token almacenado en localStorage, adjuntado por `AuthInterceptor`
- Refresh automático en 401
- Menú dinámico desde `/auth/me`
- Guards: `AuthGuard` (protege rutas), `permissionGuard` (control por rol)

## Comunicación con API

La URL base se obtiene de `ConfigService.apiUrl`, cargada desde `public/config/config.json` en el bootstrap (vía `APP_INITIALIZER`). No hay archivos `environments/`.

El `AuthInterceptor` adjunta `Authorization: Bearer <token>` a cada request y maneja refresh en 401.

## Diseño

- Paleta Fénix con variables CSS en `styles.css`
- Bootstrap Icons para iconografía
- Sidebar lateral con menú dinámico por rol
- Sin topbar
- Modales con overlay Bootstrap
