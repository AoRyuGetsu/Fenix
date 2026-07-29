import { Routes } from '@angular/router';
import { permissionGuard } from '../../core/auth/guards/permission.guard';
import { AnalysisDashboardPageComponent } from './pages/analysis-dashboard-page/analysis-dashboard-page.component';
import { PlayerAnalysisPageComponent } from './pages/player-analysis-page/player-analysis-page.component';

export default [
  {
    path: '',
    component: AnalysisDashboardPageComponent,
    canActivate: [permissionGuard],
    data: { roles: ['ADM', 'SUP', 'ENT'] },
  },
  {
    path: 'jugador/:id',
    component: PlayerAnalysisPageComponent,
  },
] satisfies Routes;
