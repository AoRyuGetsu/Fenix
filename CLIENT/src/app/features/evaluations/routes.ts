import { Routes } from '@angular/router';
import { EvaluationsPageComponent } from './pages/evaluations-page/evaluations-page.component';
import { EvaluationFillPageComponent } from './pages/evaluation-fill-page/evaluation-fill-page.component';

export default [
  { path: '', component: EvaluationsPageComponent },
  { path: 'llenar/:id', component: EvaluationFillPageComponent },
] satisfies Routes;
