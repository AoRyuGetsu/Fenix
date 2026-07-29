import { Injectable, inject, signal, computed } from '@angular/core';
import { AuthFacade } from '../../../core/auth/facades/auth.facade';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService } from '../../../core/services/message.service';
import {
  EvaluationStatsHttpService,
  TeamStatsResponse,
  PlayerStatsResponse,
  TeamItem,
} from '../services/evaluation-stats-http.service';

export interface DashboardState {
  teams: TeamItem[];
  selectedTeamId: number | null;
  teamStats: TeamStatsResponse | null;
  loading: boolean;
  error: string | null;
  selectedQuestionnaireId: number | null;
}

export interface PlayerState {
  playerStats: PlayerStatsResponse | null;
  loading: boolean;
  error: string | null;
  selectedQuestionnaireId: number | null;
}

@Injectable({ providedIn: 'root' })
export class EvaluationStatsFacade {
  private api = inject(EvaluationStatsHttpService);
  private msg = inject(MessageService);
  private auth = inject(AuthFacade);

  private dashboardState = signal<DashboardState>({
    teams: [],
    selectedTeamId: null,
    teamStats: null,
    loading: false,
    error: null,
    selectedQuestionnaireId: null,
  });

  private playerState = signal<PlayerState>({
    playerStats: null,
    loading: false,
    error: null,
    selectedQuestionnaireId: null,
  });

  dashboardVm = this.dashboardState.asReadonly();
  playerVm = this.playerState.asReadonly();

  questionnaireOptions = computed(() => {
    const stats = this.dashboardState().teamStats;
    if (!stats) return [];
    return stats.questionnaires.map(q => ({ id: q.questionnaire.id, title: q.questionnaire.title }));
  });

  playerQuestionnaireOptions = computed(() => {
    const stats = this.playerState().playerStats;
    if (!stats) return [];
    return stats.questionnaires.map(q => ({ id: q.questionnaire.id, title: q.questionnaire.title }));
  });

  currentQStats = computed(() => {
    const state = this.dashboardState();
    if (!state.teamStats || state.selectedQuestionnaireId === null) return null;
    return state.teamStats.questionnaires.find(q => q.questionnaire.id === state.selectedQuestionnaireId) ?? null;
  });

  currentPlayerQStats = computed(() => {
    const state = this.playerState();
    if (!state.playerStats || state.selectedQuestionnaireId === null) return null;
    return state.playerStats.questionnaires.find(q => q.questionnaire.id === state.selectedQuestionnaireId) ?? null;
  });

  loadTeams(): void {
    this.api.getTeams().subscribe({
      next: res => this.dashboardState.update(s => ({ ...s, teams: res.data })),
      error: err => this.dashboardState.update(s => ({ ...s, error: getErrorMessage(err, this.msg.get('es_err_load_teams')) })),
    });
  }

  loadMyTeam(): void {
    this.api.getMyTeams().subscribe({
      next: teams => {
        if (teams.length > 0) {
          this.dashboardState.update(s => ({ ...s, teams, selectedTeamId: teams[0].id }));
          this.loadTeamStats(teams[0].id);
        }
      },
      error: err => this.dashboardState.update(s => ({ ...s, error: getErrorMessage(err, this.msg.get('es_err_load_teams')) })),
    });
  }

  loadTeamStats(teamId: number): void {
    this.dashboardState.update(s => ({ ...s, loading: true, error: null, selectedTeamId: teamId }));

    this.api.getTeamStats(teamId).subscribe({
      next: stats => {
        const firstQId = stats.questionnaires.length > 0 ? stats.questionnaires[0].questionnaire.id : null;
        this.dashboardState.update(s => ({
          ...s,
          teamStats: stats,
          loading: false,
          selectedQuestionnaireId: firstQId,
        }));
      },
      error: err => this.dashboardState.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, this.msg.get('es_err_load_team')),
      })),
    });
  }

  loadPlayerStats(targetUserId: number): void {
    this.playerState.update(s => ({ ...s, loading: true, error: null }));

    this.api.getPlayerStats(targetUserId).subscribe({
      next: stats => {
        const firstQId = stats.questionnaires.length > 0 ? stats.questionnaires[0].questionnaire.id : null;
        this.playerState.update(s => ({
          ...s,
          playerStats: stats,
          loading: false,
          selectedQuestionnaireId: firstQId,
        }));
      },
      error: err => this.playerState.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, this.msg.get('es_err_load_player')),
      })),
    });
  }

  selectTeam(teamId: number): void {
    this.loadTeamStats(teamId);
  }

  selectQuestionnaire(questionnaireId: number): void {
    this.dashboardState.update(s => ({ ...s, selectedQuestionnaireId: questionnaireId }));
  }

  selectPlayerQuestionnaire(questionnaireId: number): void {
    this.playerState.update(s => ({ ...s, selectedQuestionnaireId: questionnaireId }));
  }
}
