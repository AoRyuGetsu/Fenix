import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';

export interface TrendCounts { upward: number; downward: number; stable: number; }
export interface StatsPeriod { id: number; name: string; index: number; }
export interface PeriodAverage { period_id: number; period_name: string; average: number; count?: number; }
export interface RegressionResult { slope: number; intercept: number; r_squared: number; trend: 'upward' | 'downward' | 'stable'; }
export interface TeamItem { id: number; name: string; code: string; description?: string; }
export interface PaginatedResponse<T> { data: T[]; total: number; page: number; perPage: number; }

export interface TeamStatsResponse {
  team: { id: number; name: string };
  periods: StatsPeriod[];
  trend_summary: TrendCounts;
  questionnaires: QuestionnaireTeamStats[];
}

export interface QuestionnaireTeamStats {
  questionnaire: { id: number; title: string };
  team_averages: { period_id: number; period_name: string; average: number }[];
  team_regression: RegressionResult | null;
  players: PlayerTrend[];
}

export interface PlayerTrend {
  user: { id: number; display_name: string };
  overall_averages: PeriodAverage[];
  regression: RegressionResult | null;
}

export interface PlayerStatsResponse {
  target_user: { id: number; display_name: string };
  periods: StatsPeriod[];
  questionnaires: QuestionnairePlayerStats[];
}

export interface QuestionnairePlayerStats {
  questionnaire: { id: number; title: string };
  per_question: QuestionStats[];
  overall: { averages: PeriodAverage[]; regression: RegressionResult | null };
}

export interface QuestionStats {
  question_id: number;
  question_text: string;
  question_type: string;
  averages: PeriodAverage[];
  regression: RegressionResult | null;
}

@Injectable({ providedIn: 'root' })
export class EvaluationStatsHttpService {
  private http = inject(HttpClient);
  private config = inject(ConfigService);

  getTeamStats(teamId: number, questionnaireId?: number, periodIds?: number[]): Observable<TeamStatsResponse> {
    let params = '';
    const sep = (p: string) => params ? `&${p}` : `?${p}`;
    if (questionnaireId) params += sep(`questionnaire_id=${questionnaireId}`);
    if (periodIds?.length) params += sep(`period_ids=${periodIds.join(',')}`);
    return this.http.get<TeamStatsResponse>(`${this.config.apiUrl}/evaluation-stats/team/${teamId}${params}`);
  }

  getPlayerStats(targetUserId: number, questionnaireId?: number, periodIds?: number[]): Observable<PlayerStatsResponse> {
    let params = `?target_user_id=${targetUserId}`;
    if (questionnaireId) params += `&questionnaire_id=${questionnaireId}`;
    if (periodIds?.length) params += `&period_ids=${periodIds.join(',')}`;
    return this.http.get<PlayerStatsResponse>(`${this.config.apiUrl}/evaluation-stats${params}`);
  }

  getTeams(page = 1, perPage = 50): Observable<PaginatedResponse<TeamItem>> {
    return this.http.get<PaginatedResponse<TeamItem>>(`${this.config.apiUrl}/teams?page=${page}&perPage=${perPage}`);
  }

  getMyTeams(): Observable<TeamItem[]> {
    return this.http.get<TeamItem[]>(`${this.config.apiUrl}/teams/my`);
  }
}
