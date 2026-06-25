import { Injectable, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { AuthHttpService } from '../services/auth-http.service';
import { MenuFacade } from '../../menu/facades/menu.facade';

export interface AuthState {
  user: { id: number; email: string } | null;
  token: string | null;
  refreshToken: string | null;
  roles: string[];
  permissions: string[];
  loading: boolean;
  error: string | null;
  success: string | null;
}

const STORAGE_TOKEN = 'access_token';
const STORAGE_REFRESH = 'refresh_token';

function decodeRoles(token: string): string[] {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return Array.isArray(payload.roles) ? payload.roles : [];
  } catch {
    return [];
  }
}

function loadFromStorage(): Partial<AuthState> {
  const token = localStorage.getItem(STORAGE_TOKEN);
  return {
    token,
    refreshToken: localStorage.getItem(STORAGE_REFRESH),
    roles: token ? decodeRoles(token) : [],
    permissions: [],
  };
}

@Injectable({ providedIn: 'root' })
export class AuthFacade {
  private api = inject(AuthHttpService);
  private router = inject(Router);
  private menuFacade = inject(MenuFacade);

  private state = signal<AuthState>({
    user: null,
    token: null,
    refreshToken: null,
    roles: [],
    permissions: [],
    loading: false,
    error: null,
    success: null,
    ...loadFromStorage(),
  });

  vm = this.state.asReadonly();

  constructor() {
    if (this.state().token) {
      this.menuFacade.loadFromStorage();
    }
  }

  login(email: string, password: string): void {
    this.state.update(s => ({ ...s, loading: true, error: null, success: null }));

    this.api.login(email, password).subscribe({
      next: res => {
        localStorage.setItem(STORAGE_TOKEN, res.access_token);
        localStorage.setItem(STORAGE_REFRESH, res.refresh_token);
        this.menuFacade.setMenus(res.menus);

        const roles = decodeRoles(res.access_token);

        this.state.set({
          user: res.user,
          token: res.access_token,
          refreshToken: res.refresh_token,
          roles,
          permissions: [],
          loading: false,
          error: null,
          success: 'Sesión iniciada correctamente',
        });

        setTimeout(() => this.router.navigateByUrl('/'), 1500);
      },
      error: err => {
        const message =
          err.status === 401
            ? 'Credenciales incorrectas'
            : err.error?.error ?? 'Error al iniciar sesión';

        this.state.update(s => ({ ...s, loading: false, error: message, success: null }));
      },
    });
  }

  logout(): void {
    localStorage.removeItem(STORAGE_TOKEN);
    localStorage.removeItem(STORAGE_REFRESH);
    this.menuFacade.clear();
    this.state.set({
      user: null,
      token: null,
      refreshToken: null,
      roles: [],
      permissions: [],
      loading: false,
      error: null,
      success: null,
    });
    this.router.navigateByUrl('/login');
  }

  clearMessages(): void {
    this.state.update(s => ({ ...s, error: null, success: null }));
  }

  isAuthenticated(): boolean {
    return this.state().token !== null;
  }

  roles = (): string[] => this.state().roles;

  hasRole(role: string): boolean {
    return this.state().roles.includes(role);
  }

  hasPermission(permission: string): boolean {
    return this.state().permissions.includes(permission);
  }
}
