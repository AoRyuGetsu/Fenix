import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../services/config.service';

export interface MenuItem {
  id: number;
  label: string;
  icon: string | null;
  route: string | null;
  parent_id: number | null;
  menu_order: number;
}

export interface RoleItem {
  id: number;
  code: string;
  name: string;
}

export interface LoginResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  user: {
    id: number;
    email: string;
    person: { full_name: string } | null;
  };
  roles: string[];
  menus: MenuItem[];
  catalogs?: {
    roles: RoleItem[];
    [key: string]: unknown[];
  };
}

@Injectable({ providedIn: 'root' })
export class AuthHttpService {
  private http = inject(HttpClient);
  private config = inject(ConfigService);

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.config.apiUrl}/auth/login`, { email, password });
  }

  refresh(refreshToken: string): Observable<{ access_token: string; refresh_token: string }> {
    return this.http.post<{ access_token: string; refresh_token: string }>(
      `${this.config.apiUrl}/auth/refresh`,
      { refresh_token: refreshToken },
    );
  }
}
