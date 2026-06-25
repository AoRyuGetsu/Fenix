import { Injectable, inject, isDevMode } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

const BASE_URL = isDevMode() ? 'http://localhost:8000' : '';

export interface AppConfig {
  app_name: string;
  api_url: string;
}

@Injectable({ providedIn: 'root' })
export class ConfigHttpService {
  private http = inject(HttpClient);

  getConfig(): Observable<AppConfig> {
    return this.http.get<AppConfig>(`${BASE_URL}/config`);
  }
}
