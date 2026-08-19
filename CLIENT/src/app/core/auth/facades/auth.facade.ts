import { Injectable, inject, signal, computed } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { Router } from '@angular/router';
import { AuthHttpService } from '../services/auth-http.service';
import { MenuFacade } from '../../menu/facades/menu.facade';
import { decodeJwtPayload } from '../utils/jwt';

export interface AuthState {
  user: { id: number; email: string; person: { full_name: string } | null } | null;
  token: string | null;
  refreshToken: string | null;
  expiresAt: number | null;
  roles: string[];
  permissions: string[];
  loading: boolean;
  error: string | null;
}

const STORAGE_TOKEN = 'access_token';
const STORAGE_REFRESH = 'refresh_token';
const STORAGE_USER = 'auth_user';
const STORAGE_ROLES = 'auth_roles';
const STORAGE_EXPIRES_AT = 'auth_expires_at';

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
  const rawExpiresAt = localStorage.getItem(STORAGE_EXPIRES_AT);
  const expiresAt = rawExpiresAt ? Number(rawExpiresAt) : (token ? expiresAtFromToken(token) : null);
  return {
    token,
    refreshToken: localStorage.getItem(STORAGE_REFRESH),
    expiresAt: Number.isFinite(expiresAt) ? expiresAt : null,
    user,
    roles,
    permissions: [],
  };
}

function expiresAtFromToken(token: string): number | null {
  const payload = decodeJwtPayload(token);
  return typeof payload?.exp === 'number' ? payload.exp * 1000 : null;
}

@Injectable({ providedIn: 'root' })
export class AuthFacade {
  private api = inject(AuthHttpService);
  private router = inject(Router);
  private menuFacade = inject(MenuFacade);
  private msg = inject(MessageService);

  private state = signal<AuthState>({
    user: null,
    token: null,
    refreshToken: null,
    expiresAt: null,
    roles: [],
    permissions: [],
    loading: false,
    error: null,
    ...loadFromStorage(),
  });

  private expiryTimer: ReturnType<typeof setTimeout> | null = null;

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.state(), t: this.t }));

  isAuthenticated = computed(() => this.state().token !== null);

  isSessionValid = computed(() => {
    const { token, expiresAt } = this.state();
    return token !== null && expiresAt !== null && Date.now() < expiresAt;
  });

  authRoles = computed(() => this.state().roles);
  roles = this.authRoles;

  login(email: string, password: string): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.login(email, password).subscribe({
      next: res => {
        const expiresAt = Date.now() + res.expires_in * 1000;
        localStorage.setItem(STORAGE_TOKEN, res.access_token);
        localStorage.setItem(STORAGE_REFRESH, res.refresh_token);
        localStorage.setItem(STORAGE_USER, JSON.stringify(res.user));
        localStorage.setItem(STORAGE_ROLES, JSON.stringify(res.roles));
        localStorage.setItem(STORAGE_EXPIRES_AT, String(expiresAt));
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
          expiresAt,
          roles: res.roles,
          permissions: [],
          loading: false,
          error: null,
        }));

        this.scheduleExpiry();
        this.router.navigateByUrl('/');
      },
      error: err => {
        this.state.update(s => ({ ...s, loading: false, error: getErrorMessage(err, 'cr_err_login_failed', this.msg) }));
      },
    });
  }

  clearSession(): void {
    this.clearExpiryTimer();
    localStorage.removeItem(STORAGE_TOKEN);
    localStorage.removeItem(STORAGE_REFRESH);
    localStorage.removeItem(STORAGE_USER);
    localStorage.removeItem(STORAGE_ROLES);
    localStorage.removeItem(STORAGE_EXPIRES_AT);
    Object.keys(localStorage).filter(k => k.startsWith('catalogs.')).forEach(k => localStorage.removeItem(k));
    this.menuFacade.clear();
    this.state.update(s => ({
      ...s,
      user: null,
      token: null,
      refreshToken: null,
      expiresAt: null,
      roles: [],
      permissions: [],
      loading: false,
      error: null,
    }));
  }

  logout(): void {
    this.clearSession();
    this.router.navigateByUrl('/login');
  }

  refreshTokens(accessToken: string, refreshToken: string): void {
    const expiresAt = expiresAtFromToken(accessToken);
    localStorage.setItem(STORAGE_TOKEN, accessToken);
    localStorage.setItem(STORAGE_REFRESH, refreshToken);
    if (expiresAt !== null) {
      localStorage.setItem(STORAGE_EXPIRES_AT, String(expiresAt));
    }
    this.state.update(s => ({
      ...s,
      token: accessToken,
      refreshToken,
      expiresAt,
    }));
    this.scheduleExpiry();
  }

  private scheduleExpiry(): void {
    this.clearExpiryTimer();
    const { expiresAt } = this.state();
    if (expiresAt === null) return;

    const delay = expiresAt - Date.now();
    if (delay <= 0) {
      this.logout();
      return;
    }
    this.expiryTimer = setTimeout(() => this.logout(), delay);
  }

  private clearExpiryTimer(): void {
    if (this.expiryTimer !== null) {
      clearTimeout(this.expiryTimer);
      this.expiryTimer = null;
    }
  }

  init(): void {
    if (!this.state().token) return;

    if (!this.isSessionValid()) {
      this.logout();
      return;
    }
    this.menuFacade.loadFromStorage();
    this.scheduleExpiry();
  }
}
