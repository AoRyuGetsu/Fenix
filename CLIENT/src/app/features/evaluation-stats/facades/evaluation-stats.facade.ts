import { Injectable, inject, signal, computed } from '@angular/core';
import { AuthFacade } from '../../../core/auth/facades/auth.facade';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import {
  EvaluationStatsHttpService,
  PlayerStatsResponse,
} from '../services/evaluation-stats-http.service';
import { PlayerListItem } from '../../../shared/components/player-list/player-list.types';
import { GtPagination } from '../../../shared/components/gtable/gtable.types';

export interface DashboardState {
  roster: PlayerListItem[];
  total: number;
  loading: boolean;
  error: string | null;
  selectedPlayerId: number | null;
  rosterSearch: string;
  rosterPage: number;
  perPage: number;
  teamId: number | null;
}

export interface PlayerState {
  playerStats: PlayerStatsResponse | null;
  loading: boolean;
  error: string | null;
  selectedQuestionnaireId: number | null;
  currentQuestionIndex: number;
  viewMode: 'analysis' | 'historial';
}

@Injectable({ providedIn: 'root' })
export class EvaluationStatsFacade {
  private api = inject(EvaluationStatsHttpService);
  private msg = inject(MessageService);
  private auth = inject(AuthFacade);

  private dashboardState = signal<DashboardState>({
    roster: [],
    total: 0,
    loading: false,
    error: null,
    selectedPlayerId: null,
    rosterSearch: '',
    rosterPage: 1,
    perPage: 20,
    teamId: null,
  });

  private playerState = signal<PlayerState>({
    playerStats: null,
    loading: false,
    error: null,
    selectedQuestionnaireId: null,
    currentQuestionIndex: 0,
    viewMode: 'analysis',
  });

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  isJug = computed(() => this.auth.authRoles().includes('JUG'));

  isRep = computed(() => this.auth.authRoles().includes('REP'));

  canToggleModes = computed(() => !(this.isJug() || this.isRep()));

  dashboardVm = computed(() => ({
    ...this.dashboardState(),
    isJug: this.isJug(),
    isRep: this.isRep(),
    canToggleModes: this.canToggleModes(),
    t: this.t,
  }));

  questionCount = computed(() => this.currentPlayerQStats()?.per_question.length ?? 0);

  currentQuestion = computed(() => {
    const q = this.currentPlayerQStats();
    const idx = this.playerState().currentQuestionIndex;
    return q?.per_question[idx] ?? null;
  });

  overallAverages = computed(() => this.currentPlayerQStats()?.overall.averages ?? []);

  overallHistorialRegression = computed(() => this.currentPlayerQStats()?.overall.historial_regression ?? null);

  currentQuestionAverages = computed(() => this.currentQuestion()?.averages ?? []);

  currentQuestionHistorialRegression = computed(() => this.currentQuestion()?.historial_regression ?? null);

  playerVm = computed(() => {
    const s = this.playerState();
    const count = this.questionCount();
    return {
      ...s,
      questionIndex: s.currentQuestionIndex,
      questionCount: count,
      canGoPrev: s.currentQuestionIndex > 0,
      canGoNext: s.currentQuestionIndex < count - 1,
      overallAverages: this.overallAverages(),
      overallHistorialRegression: this.overallHistorialRegression(),
      currentQuestionAverages: this.currentQuestionAverages(),
      currentQuestionHistorialRegression: this.currentQuestionHistorialRegression(),
      canToggleModes: this.canToggleModes(),
      t: this.t,
    };
  });

  rosterPaged = computed(() => this.dashboardState().roster);

  rosterPagination = computed<GtPagination>(() => {
    const s = this.dashboardState();
    return { page: s.rosterPage, perPage: s.perPage, total: s.total };
  });

  selectedPlayerName = computed(() => {
    const s = this.dashboardState();
    return s.roster.find(p => p.id === s.selectedPlayerId)?.display_name ?? null;
  });

  playerQuestionnaireOptions = computed(() => {
    const stats = this.playerState().playerStats;
    if (!stats) return [];
    return stats.questionnaires.map(q => ({ id: q.questionnaire.id, title: q.questionnaire.title }));
  });

  currentPlayerQStats = computed(() => {
    const state = this.playerState();
    if (!state.playerStats || state.selectedQuestionnaireId === null) return null;
    return state.playerStats.questionnaires.find(q => q.questionnaire.id === state.selectedQuestionnaireId) ?? null;
  });

  loadMyTeam(): void {
    this.dashboardState.update(s => ({ ...s, loading: true, error: null }));

    this.api.getMyTeams().subscribe({
      next: res => {
        if (res.data.length > 0) {
          const teamId = res.data[0].id;
          this.dashboardState.update(s => ({ ...s, teamId }));
          this.fetchRoster(1);
        } else {
          this.dashboardState.update(s => ({ ...s, loading: false, roster: [], total: 0 }));
        }
      },
      error: err => this.dashboardState.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'es_err_load_team', this.msg),
      })),
    });
  }

  loadAllPlayers(): void {
    this.dashboardState.update(s => ({ ...s, teamId: null }));
    this.fetchRoster(1);
  }

  loadMyAnalysis(): void {
    const user = this.auth.vm().user;
    if (!user) return;

    const me: PlayerListItem = { id: user.id, display_name: user.person?.full_name ?? user.email };
    this.dashboardState.update(s => ({
      ...s,
      roster: [me],
      total: 1,
      loading: false,
      error: null,
      selectedPlayerId: user.id,
      teamId: null,
    }));
    this.loadPlayerStats(user.id);
  }

  loadRepresentedPlayers(): void {
    this.dashboardState.update(s => ({ ...s, teamId: null, rosterSearch: '' }));
    this.fetchRoster(1);
  }

  private fetchRoster(page: number): void {
    const s = this.dashboardState();
    this.dashboardState.update(state => ({ ...state, rosterPage: page, loading: true, error: null }));

    const request = this.isRep()
      ? this.api.getMyRepresented(s.rosterSearch, page, s.perPage)
      : this.api.getAllPlayers(s.rosterSearch, s.teamId ?? undefined, page, s.perPage);

    request.subscribe({
      next: res => {
        const roster: PlayerListItem[] = res.data.map(p => ({ id: p.id, display_name: p.display_name }));
        this.applyRoster(roster, res.total);
      },
      error: err => this.dashboardState.update(state => ({
        ...state,
        loading: false,
        error: getErrorMessage(err, 'es_err_load_players', this.msg),
      })),
    });
  }

  private applyRoster(roster: PlayerListItem[], total: number): void {
    this.dashboardState.update(s => ({
      ...s,
      roster,
      total,
      loading: false,
      selectedPlayerId: roster[0]?.id ?? null,
    }));

    if (roster.length > 0) {
      this.loadPlayerStats(roster[0].id);
    }
  }

  setRosterSearch(term: string): void {
    this.dashboardState.update(s => ({ ...s, rosterSearch: term }));
    this.fetchRoster(1);
  }

  changeRosterPage(page: number): void {
    this.fetchRoster(page);
  }

  selectPlayer(playerId: number): void {
    this.dashboardState.update(s => ({ ...s, selectedPlayerId: playerId }));
    this.loadPlayerStats(playerId);
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
          currentQuestionIndex: 0,
          viewMode: this.canToggleModes() ? s.viewMode : 'historial',
        }));
      },
      error: err => this.playerState.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'es_err_load_player', this.msg),
      })),
    });
  }

  selectPlayerQuestionnaire(questionnaireId: number): void {
    this.playerState.update(s => ({ ...s, selectedQuestionnaireId: questionnaireId, currentQuestionIndex: 0 }));
  }

  setViewMode(mode: 'analysis' | 'historial'): void {
    if (!this.canToggleModes()) return;
    this.playerState.update(s => ({ ...s, viewMode: mode }));
  }

  nextQuestion(): void {
    const count = this.questionCount();
    const idx = this.playerState().currentQuestionIndex;
    if (idx < count - 1) {
      this.playerState.update(s => ({ ...s, currentQuestionIndex: s.currentQuestionIndex + 1 }));
    }
  }

  previousQuestion(): void {
    const idx = this.playerState().currentQuestionIndex;
    if (idx > 0) {
      this.playerState.update(s => ({ ...s, currentQuestionIndex: s.currentQuestionIndex - 1 }));
    }
  }
}