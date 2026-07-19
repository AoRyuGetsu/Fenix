import { Routes } from '@angular/router';
import { LoginPageComponent } from './pages/login-page/login-page.component';
import { NotFoundPageComponent } from './pages/not-found-page/not-found-page.component';

export default [
  { path: '', component: LoginPageComponent },
  { path: '**', component: NotFoundPageComponent },
] satisfies Routes;
