import { Injectable, inject, signal } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { Router } from '@angular/router';
import { AuthHttpService } from '../services/auth-http.service';
import { MenuFacade } from '../../menu/facades/menu.facade';

export interface AuthState {
  user: { id: number; email: string; person: { full_name: string } | null } | null;
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
const STORAGE_USER = 'auth_user';
const STORAGE_ROLES = 'auth_roles';

function loadFromStorage(): Partial<AuthState> {
  const token = localStorage.getItem(STORAGE_TOKEN);
  let user = null;
  const raw = localStorage.getItem(STORAGE_USER);
  if (raw) {
    try { user = JSON.parse(raw); } catch { /* ignore */ }
  }
  let roles: string[] = [];
  const rawRoles = localStorage.getItem(STORAGE_ROLES);
  if (rawRoles) {
    try { roles = JSON.parse(rawRoles); } catch { /* ignore */ }
  }
  return {
    token,
    refreshToken: localStorage.getItem(STORAGE_REFRESH),
    user,
    roles,
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
        localStorage.setItem(STORAGE_USER, JSON.stringify(res.user));
        localStorage.setItem(STORAGE_ROLES, JSON.stringify(res.roles));
        this.menuFacade.setMenus(res.menus);

        if (res.catalogs) {
          for (const [key, value] of Object.entries(res.catalogs)) {
            localStorage.setItem(`catalogs.${key}`, JSON.stringify(value));
          }
        }

        this.state.update(s => ({
          ...s,
          user: res.user,
          token: res.access_token,
          refreshToken: res.refresh_token,
          roles: res.roles,
          permissions: [],
          loading: false,
          error: null,
          success: 'Sesión iniciada correctamente',
        }));

        setTimeout(() => this.router.navigateByUrl('/'), 1500);
      },
      error: err => {
        this.state.update(s => ({ ...s, loading: false, error: getErrorMessage(err, 'Error al iniciar sesión'), success: null }));
      },
    });
  }

  logout(): void {
    localStorage.removeItem(STORAGE_TOKEN);
    localStorage.removeItem(STORAGE_REFRESH);
    localStorage.removeItem(STORAGE_USER);
    localStorage.removeItem(STORAGE_ROLES);
    Object.keys(localStorage).filter(k => k.startsWith('catalogs.')).forEach(k => localStorage.removeItem(k));
    this.menuFacade.clear();
    this.state.update(s => ({
      ...s,
      user: null,
      token: null,
      refreshToken: null,
      roles: [],
      permissions: [],
      loading: false,
      error: null,
      success: null,
    }));
    this.router.navigateByUrl('/login');
  }

  clearMessages(): void {
    this.state.update(s => ({ ...s, error: null, success: null }));
  }

  isAuthenticated(): boolean {
    return this.state().token !== null;
  }

  roles = (): string[] => this.state().roles;
}
