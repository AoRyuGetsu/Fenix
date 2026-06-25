import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthFacade } from '../facades/auth.facade';

export const authRedirectGuard: CanActivateFn = () => {
  const router = inject(Router);
  const authFacade = inject(AuthFacade);

  if (authFacade.isAuthenticated()) {
    return router.parseUrl('/welcome');
  }

  return router.parseUrl('/login');
};
