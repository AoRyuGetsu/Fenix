import { Injectable, inject, isDevMode } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

const BASE_URL = isDevMode() ? 'http://localhost:8000' : '';

export interface MenuItem {
  id: number;
  label: string;
  icon: string | null;
  route: string | null;
  parent_id: number | null;
  menu_order: number;
}

export interface LoginResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  user: {
    id: number;
    email: string;
  };
  menus: MenuItem[];
}

export interface MeResponse {
  id: number;
  email: string;
  roles: string[];
  permissions: string[];
  menus: MenuItem[];
}

@Injectable({ providedIn: 'root' })
export class AuthHttpService {
  private http = inject(HttpClient);

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${BASE_URL}/auth/login`, { email, password });
  }

  refresh(refreshToken: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${BASE_URL}/auth/refresh`, { refresh_token: refreshToken });
  }

  me(): Observable<MeResponse> {
    return this.http.get<MeResponse>(`${BASE_URL}/auth/me`);
  }
}
