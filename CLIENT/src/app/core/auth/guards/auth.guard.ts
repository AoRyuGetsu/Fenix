import { Injectable, inject } from '@angular/core';
import { CanActivate, Router, UrlTree } from '@angular/router';
import { AuthFacade } from '../facades/auth.facade';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  private auth = inject(AuthFacade);
  private router = inject(Router);

  canActivate(): boolean | UrlTree {
    if (this.auth.isSessionValid()) {
      return true;
    }
    this.auth.clearSession();
    return this.router.parseUrl('/login');
  }
}
