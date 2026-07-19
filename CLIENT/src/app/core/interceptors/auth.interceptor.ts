import { HttpErrorResponse, HttpHandlerFn, HttpInterceptorFn, HttpRequest } from '@angular/common/http';
import { inject } from '@angular/core';
import { Observable, Subject, catchError, switchMap, take, throwError } from 'rxjs';
import { AuthHttpService } from '../auth/services/auth-http.service';

let isRefreshing = false;
let refreshSubject: Subject<string | null> | null = null;

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const authHttp = inject(AuthHttpService);
  const token = localStorage.getItem('access_token');

  if (token && !req.url.includes('/auth/login') && !req.url.includes('/auth/refresh') && !req.url.includes('/config/config.json')) {
    req = req.clone({ setHeaders: { Authorization: `Bearer ${token}` } });
  }

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && token && !req.url.includes('/auth/login') && !req.url.includes('/auth/refresh')) {
        return handle401Error(req, next, authHttp);
      }
      return throwError(() => error);
    }),
  );
};

function handle401Error(req: HttpRequest<unknown>, next: HttpHandlerFn, authHttp: AuthHttpService) {
  if (!isRefreshing) {
    isRefreshing = true;
    refreshSubject = new Subject<string | null>();

    authHttp.refresh(localStorage.getItem('refresh_token') ?? '').subscribe({
      next: (res) => {
        isRefreshing = false;
        localStorage.setItem('access_token', res.access_token);
        localStorage.setItem('refresh_token', res.refresh_token);
        refreshSubject!.next(res.access_token);
        refreshSubject!.complete();
      },
      error: () => {
        isRefreshing = false;
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('auth_user');
        localStorage.removeItem('auth_roles');
        Object.keys(localStorage).filter(k => k.startsWith('catalogs.')).forEach(k => localStorage.removeItem(k));
        refreshSubject!.next(null);
        refreshSubject!.complete();
        window.location.href = '/login';
      },
    });
  }

  return refreshSubject!.pipe(
    take(1),
    switchMap((newToken): Observable<import('@angular/common/http').HttpEvent<unknown>> => {
      if (!newToken) {
        return throwError(() => new Error('Sesión expirada'));
      }
      return next(req.clone({ setHeaders: { Authorization: `Bearer ${newToken}` } }));
    }),
  );
}
