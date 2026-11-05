import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';

export interface StatsPeriod { id: number; name: string; index: number; }
export interface PeriodAverage { period_id: number; period_name: string; index: number; average: number; count?: number; }
export interface RegressionResult {
  slope: number;
  intercept: number;
  r_squared: number;
  trend: 'upward' | 'downward' | 'stable';
  sse: number;
  std_error_slope: number;
  std_error_intercept: number;
  n: number;
}
export interface Observation {
  index: number;
  evaluated_at: string;
  x_days: number;
  value: number;
  fitted: number;
  residual: number;
}
export interface TeamItem { id: number; name: string; code: string; description?: string; }
export interface PaginatedResponse<T> { data: T[]; total: number; page: number; perPage: number; }

export interface TeamDetailResponse {
  id: number;
  name: string;
  code: string;
  description: string | null;
  player_count: number;
  players: { id: number; email: string; display_name: string }[];
}

export interface PlayerSummaryResponse {
  id: number;
  email: string;
  display_name: string;
}

export interface PlayerStatsResponse {
  target_user: {
    id: number;
    display_name: string;
    email?: string | null;
    birth_date?: string | null;
    height_cm?: number | null;
    weight_kg?: number | null;
    team?: string | null;
  };
  periods: StatsPeriod[];
  questionnaires: QuestionnairePlayerStats[];
}

export interface QuestionnairePlayerStats {
  questionnaire: { id: number; title: string };
  per_question: QuestionStats[];
  overall: {
    averages: PeriodAverage[];
    observations: Observation[];
    regression: RegressionResult | null;
    historial_regression: RegressionResult | null;
  };
  last_observation?: string | null;
  last_observation_at?: string | null;
  last_evaluator?: string | null;
}

export interface QuestionStats {
  question_id: number;
  question_text: string;
  question_type: string;
  averages: PeriodAverage[];
  observations: Observation[];
  regression: RegressionResult | null;
  historial_regression: RegressionResult | null;
}

@Injectable({ providedIn: 'root' })
export class EvaluationStatsHttpService {
  private http = inject(HttpClient);
  private config = inject(ConfigService);

  getPlayerStats(targetUserId: number, questionnaireId?: number, periodIds?: number[]): Observable<PlayerStatsResponse> {
    let params = `?target_user_id=${targetUserId}`;
    if (questionnaireId) params += `&questionnaire_id=${questionnaireId}`;
    if (periodIds?.length) params += `&period_ids=${periodIds.join(',')}`;
    return this.http.get<PlayerStatsResponse>(`${this.config.apiUrl}/evaluation-stats${params}`);
  }

  getMyTeams(search?: string, page = 1, perPage?: number): Observable<PaginatedResponse<TeamItem>> {
    let params = `?page=${page}${perPage ? `&perPage=${perPage}` : ''}`;
    if (search) params += `&search=${encodeURIComponent(search)}`;
    return this.http.get<PaginatedResponse<TeamItem>>(`${this.config.apiUrl}/teams/my${params}`);
  }

  getMyRepresented(search?: string, page = 1, perPage?: number): Observable<PaginatedResponse<PlayerSummaryResponse>> {
    let params = `?page=${page}${perPage ? `&perPage=${perPage}` : ''}`;
    if (search) params += `&search=${encodeURIComponent(search)}`;
    return this.http.get<PaginatedResponse<PlayerSummaryResponse>>(`${this.config.apiUrl}/representatives/my/players${params}`);
  }

  getTeam(teamId: number): Observable<TeamDetailResponse> {
    return this.http.get<TeamDetailResponse>(`${this.config.apiUrl}/teams/${teamId}`);
  }

  getAllPlayers(search?: string, teamId?: number, page = 1, perPage?: number): Observable<PaginatedResponse<PlayerSummaryResponse>> {
    let params = `?page=${page}${perPage ? `&perPage=${perPage}` : ''}`;
    if (search) params += `&search=${encodeURIComponent(search)}`;
    if (teamId) params += `&team_id=${teamId}`;
    return this.http.get<PaginatedResponse<PlayerSummaryResponse>>(`${this.config.apiUrl}/users/players${params}`);
  }
}