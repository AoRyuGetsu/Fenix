import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ConfigService } from '../../../core/services/config.service';

export interface DashboardStats {
  questionnaires_total: number;
  users_by_role: Record<string, number>;
  my_evaluations_total: number;
  evaluations_by_period: { period_id: number; period_name: string; total: number }[];
}

@Injectable({ providedIn: 'root' })
export class WelcomeHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getStats() {
    return this.http.get<DashboardStats>(`${this.configService.apiUrl}/dashboard`);
  }
}
