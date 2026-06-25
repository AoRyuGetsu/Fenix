import { Routes } from '@angular/router';
import { LoginPageComponent } from './pages/login-page/login-page.component';
import { NotFoundPageComponent } from './pages/not-found-page/not-found-page.component';
import { NotLoggedInPageComponent } from './pages/not-logged-in-page/not-logged-in-page.component';

export default [
  { path: '', component: LoginPageComponent },
  { path: 'not-logged-in', component: NotLoggedInPageComponent },
  { path: '**', component: NotFoundPageComponent },
] satisfies Routes;
