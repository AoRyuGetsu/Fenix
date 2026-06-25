# Analítica — Frontend

SPA en Angular 19+ (standalone components, signals) con Bootstrap 5 y paleta Glass & Graphite.

## Requisitos

- Node.js >= 20
- Angular CLI >= 19

## Desarrollo

```bash
ng serve          # http://localhost:4200
ng build          # producción → dist/
ng test           # pruebas unitarias
```

## Estructura

```
src/app/
├── core/           ← Estado global (auth, menu, config, layout, guards)
├── shared/         ← Componentes reutilizables y utilidades
├── features/       ← Módulos funcionales (pendientes)
├── app.routes.ts   ← Rutas raíz
└── main.ts         ← Entrypoint
```

## Comunicación con API

El proxy de desarrollo redirige `/api/*` al backend en `http://localhost:8000`. Ver `angular.json` para detalles.

## Diseño

- Sistema Material-inspired con paleta Glass & Graphite
- Variables CSS globales en `styles.css`
- Bootstrap Icons para iconografía
- Topbar primary, sidebar blanco
