import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

export interface AppConfigJson {
  apiUrl: string;
  logoUrl: string;
  backgroundUrl: string;
}

@Injectable({ providedIn: 'root' })
export class ConfigService {
  private http = inject(HttpClient);
  private _apiUrl = '';
  private _logoUrl = '';
  private _backgroundUrl = '';

  get apiUrl(): string {
    return this._apiUrl;
  }

  get logoUrl(): string {
    return this._logoUrl;
  }

  get backgroundUrl(): string {
    return this._backgroundUrl;
  }

  load(): Promise<void> {
    return firstValueFrom(
      this.http.get<AppConfigJson>('/config/config.json'),
    ).then((data) => {
        this._apiUrl = data?.apiUrl ?? '';
        this._logoUrl = data?.logoUrl ?? '';
        this._backgroundUrl = data?.backgroundUrl ?? '';
      });
  }
}
