import { Routes } from '@angular/router';
import { AuthGuard } from './core/auth/guards/auth.guard';
import { authRedirectGuard } from './core/auth/guards/auth-redirect.guard';
import { permissionGuard } from './core/auth/guards/permission.guard';
import { AppShellComponent } from './core/layout/app-shell/app-shell.component';
import { NotLoggedInPageComponent } from './core/auth/pages/not-logged-in-page/not-logged-in-page.component';

export const routes: Routes = [
  {
    path: '',
    canActivate: [authRedirectGuard],
    loadComponent: () =>
      import('./core/pages/empty-route/empty-route.component').then(
        (m) => m.EmptyRouteComponent,
      ),
    pathMatch: 'full',
  },
  {
    path: 'login',
    loadChildren: () => import('./core/auth/routes'),
  },
  {
    path: 'not-logged-in',
    component: NotLoggedInPageComponent,
  },
  {
    path: 'migrate',
    loadComponent: () =>
      import('./core/pages/migrate-page/migrate-page.component').then(
        (m) => m.MigratePageComponent,
      ),
  },
  {
    path: '',
    component: AppShellComponent,
    canActivate: [AuthGuard],
    children: [
      {
        path: 'welcome',
        loadChildren: () => import('./features/welcome/routes'),
      },
      {
        path: 'configuracion/roles-permisos',
        loadComponent: () =>
          import('./core/auth/pages/roles-permisos/roles-permisos-page.component').then(
            (m) => m.RolesPermisosPageComponent,
          ),
      },
      {
        path: 'configuracion/usuarios',
        loadChildren: () => import('./features/users/routes'),
      },
      {
        path: 'configuracion/cuestionarios',
        loadChildren: () => import('./features/questionnaire/routes'),
      },
      {
        path: 'configuracion/periodos',
        canActivate: [permissionGuard],
        data: { roles: ['ADM'] },
        loadChildren: () => import('./features/periods/routes'),
      },
      {
        path: 'configuracion/equipos',
        canActivate: [permissionGuard],
        data: { roles: ['ADM'] },
        loadChildren: () => import('./features/teams/routes'),
      },
      {
        path: 'evaluaciones/estadisticas',
        redirectTo: '/analisis',
      },
      {
        path: 'evaluaciones',
        canActivate: [permissionGuard],
        data: { roles: ['ENT'] },
        loadChildren: () => import('./features/evaluations/routes'),
      },
      {
        path: 'analisis',
        loadChildren: () => import('./features/evaluation-stats/routes'),
      },
      {
        path: 'representados',
        canActivate: [permissionGuard],
        data: { roles: ['REP'] },
        loadChildren: () => import('./features/representados/routes'),
      },
      {
        path: '**',
        loadComponent: () =>
          import('./core/pages/not-found-inline/not-found-inline.component').then(
            (m) => m.NotFoundInlineComponent,
          ),
      },
    ],
  },
];
