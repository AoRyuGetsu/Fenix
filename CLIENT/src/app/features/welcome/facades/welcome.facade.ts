import { Injectable, inject, signal } from '@angular/core';
import { WelcomeHttpService, DashboardStats } from '../services/welcome-http.service';

export interface WelcomeState {
  stats: DashboardStats | null;
  loading: boolean;
  error: string | null;
}

@Injectable({ providedIn: 'root' })
export class WelcomeFacade {
  private api = inject(WelcomeHttpService);

  private state = signal<WelcomeState>({
    stats: null,
    loading: false,
    error: null,
  });

  vm = this.state.asReadonly();

  load(): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.getStats().subscribe({
      next: stats => this.state.update(s => ({ ...s, stats, loading: false })),
      error: err => this.state.update(s => ({ ...s, loading: false, error: err.message ?? 'Error al cargar dashboard' })),
    });
  }
}
