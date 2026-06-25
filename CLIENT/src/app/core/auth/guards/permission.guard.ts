import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthFacade } from '../facades/auth.facade';

export const permissionGuard: CanActivateFn = (route) => {
  const router = inject(Router);
  const authFacade = inject(AuthFacade);

  const requiredRoles = route.data?.['roles'] as string[] | undefined;

  if (!requiredRoles || requiredRoles.length === 0) {
    return true;
  }

  const userRoles = authFacade.roles();

  const hasRole = requiredRoles.some((role) => userRoles.includes(role));

  if (!hasRole) {
    return router.parseUrl('/welcome');
  }

  return true;
};
