import { Routes } from '@angular/router';
import { permissionGuard } from '../../core/auth/guards/permission.guard';
import { AnalysisDashboardPageComponent } from './pages/analysis-dashboard-page/analysis-dashboard-page.component';

export default [
  {
    path: '',
    component: AnalysisDashboardPageComponent,
    canActivate: [permissionGuard],
    data: { roles: ['ADM', 'SUP', 'ENT', 'REP', 'JUG'] },
  },
] satisfies Routes;