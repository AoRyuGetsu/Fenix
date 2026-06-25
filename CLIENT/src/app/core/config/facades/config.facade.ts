import { Injectable, inject, signal } from '@angular/core';
import { ConfigHttpService } from '../services/config-http.service';

export interface ConfigState {
  appName: string;
  apiUrl: string;
}

@Injectable({ providedIn: 'root' })
export class ConfigFacade {
  private api = inject(ConfigHttpService);

  private state = signal<ConfigState>({
    appName: 'Analítica',
    apiUrl: '',
  });

  vm = this.state.asReadonly();

  constructor() {
    this.load();
  }

  private load(): void {
    this.api.getConfig().subscribe({
      next: res => this.state.set({ appName: res.app_name, apiUrl: res.api_url }),
    });
  }
}
