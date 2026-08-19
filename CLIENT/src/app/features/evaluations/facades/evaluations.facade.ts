import { Injectable, inject, signal, computed } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import {
  EvaluationsHttpService,
  MyQuestionnaireItem,
  QuestionnaireDetail,
  UserItem,
  PeriodItem,
  PlayerSpecData,
} from '../services/evaluations-http.service';

export interface EvaluatedStudent {
  evaluation_id: number;
  user: UserItem;
  answers: { id: number; question_id: number; value: string }[];
}

export interface EvaluationFillState {
  questionnaire: QuestionnaireDetail | null;
  periods: PeriodItem[];
  selectedPeriodId: number | null;
  unevaluated: UserItem[];
  evaluated: EvaluatedStudent[];
  usersLoading: boolean;
  questionsLoading: boolean;
  selectedUserId: number | null;
  editingEvaluationId: number | null;
  answers: Record<number, string>;
  saving: boolean;
  error: string | null;
  success: string | null;
  page: number;
  perPage: number;
  total: number;
  showPlayerInfoModal: boolean;
  playerInfoUser: UserItem | null;
  playerInfoSpec: PlayerSpecData | null;
  playerInfoLoading: boolean;
}

export interface EvaluationsStudentItem {
  user: UserItem;
  isEvaluated: boolean;
  evaluationId: number | null;
}

export interface EvaluationsState {
  items: MyQuestionnaireItem[];
  total: number;
  page: number;
  perPage: number;
  loading: boolean;
  error: string | null;
  success: string | null;
  searchTerm: string;
}

@Injectable({ providedIn: 'root' })
export class EvaluationsFacade {
  private api = inject(EvaluationsHttpService);
  private msg = inject(MessageService);

  private listState = signal<EvaluationsState>({
    items: [],
    total: 0,
    page: 1,
    perPage: 20,
    loading: false,
    error: null,
    success: null,
    searchTerm: '',
  });

  private fillState = signal<EvaluationFillState>({
    questionnaire: null,
    periods: [],
    selectedPeriodId: null,
    unevaluated: [],
    evaluated: [],
    usersLoading: false,
    questionsLoading: false,
    selectedUserId: null,
    editingEvaluationId: null,
    answers: {},
    saving: false,
    error: null,
    success: null,
    page: 1,
    perPage: 20,
    total: 0,
    showPlayerInfoModal: false,
    playerInfoUser: null,
    playerInfoSpec: null,
    playerInfoLoading: false,
  });

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.listState(), t: this.t }));

  private studentsList = computed<EvaluationsStudentItem[]>(() => {
    const s = this.fillState();
    const mapped: EvaluationsStudentItem[] = [
      ...s.unevaluated.map(u => ({ user: u, isEvaluated: false, evaluationId: null })),
      ...s.evaluated.map(e => ({ user: e.user, isEvaluated: true, evaluationId: e.evaluation_id })),
    ];
    return mapped.sort((a, b) =>
      (a.user.display_name || a.user.email || '').localeCompare(b.user.display_name || b.user.email || '')
    );
  });

  private selectedUserDisplayName = computed(() => {
    const s = this.fillState();
    if (s.selectedUserId === null) return '';
    const student = this.studentsList().find(st => st.user.id === s.selectedUserId);
    return student ? (student.user.display_name || student.user.email || '') : '';
  });

  fillVm = computed(() => {
    const s = this.fillState();
    return {
      ...s,
      studentsList: this.studentsList(),
      selectedUserDisplayName: this.selectedUserDisplayName(),
      totalPages: Math.max(1, Math.ceil(s.total / s.perPage)),
      t: this.t,
    };
  });

  load(page = 1): void {
    this.listState.update(s => ({ ...s, loading: true, error: null }));

    this.api.getMyQuestionnaires(page, this.listState().perPage, this.listState().searchTerm || undefined).subscribe({
      next: res => this.listState.update(s => ({
        ...s,
        items: res.data,
        total: res.total,
        page: res.page,
        perPage: res.perPage,
        loading: false,
      })),
      error: err => this.listState.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'eval_err_load_evaluations', this.msg),
      })),
    });
  }

  setSearchTerm(term: string): void {
    this.listState.update(s => ({ ...s, searchTerm: term, page: 1 }));
    this.load(1);
  }

  changePage(page: number): void {
    this.load(page);
  }

  private findCurrentPeriod(periods: PeriodItem[]): number | null {
    const today = new Date().toISOString().slice(0, 10);
    const match = periods.find(p => today >= p.start_date && today <= p.end_date);
    return match ? match.id : (periods.length > 0 ? periods[periods.length - 1].id : null);
  }

  loadFillData(questionnaireId: number, page = 1): void {
    this.fillState.update(s => ({
      ...s,
      questionnaire: null,
      periods: [],
      selectedPeriodId: null,
      unevaluated: [],
      evaluated: [],
      usersLoading: true,
      questionsLoading: true,
      selectedUserId: null,
      editingEvaluationId: null,
      answers: {},
      saving: false,
      error: null,
      success: null,
      page,
    }));

    this.api.getPeriods().subscribe({
      next: periods => {
        const selectedPeriodId = this.findCurrentPeriod(periods);
        this.fillState.update(s => ({
          ...s,
          periods,
          selectedPeriodId,
        }));

        if (selectedPeriodId !== null) {
          this.loadPeriodTargets(questionnaireId, selectedPeriodId);
        }
      },
      error: err => this.fillState.update(s => ({
        ...s,
        error: getErrorMessage(err, 'eval_err_load_periods', this.msg),
      })),
    });

    this.api.getQuestionnaire(questionnaireId).subscribe({
      next: (q: QuestionnaireDetail) => {
        this.fillState.update(s => ({
          ...s,
          questionnaire: q,
          questionsLoading: false,
        }));
      },
      error: (err: any) => this.fillState.update(s => ({
        ...s,
        questionsLoading: false,
        usersLoading: false,
        error: getErrorMessage(err, 'eval_err_load_questionnaire', this.msg),
      })),
    });
  }

  private loadPeriodTargets(questionnaireId: number, periodId: number, page = 1): void {
    this.fillState.update(s => ({
      ...s,
      usersLoading: true,
      selectedUserId: null,
      editingEvaluationId: null,
      answers: {},
      page,
    }));

    this.api.getPeriodTargets(periodId, questionnaireId, page, this.fillState().perPage).subscribe({
      next: res => this.fillState.update(s => ({
        ...s,
        unevaluated: res.data.unevaluated,
        evaluated: res.data.evaluated,
        total: res.total,
        page: res.page,
        perPage: res.perPage,
        usersLoading: false,
      })),
      error: err => this.fillState.update(s => ({
        ...s,
        usersLoading: false,
        error: getErrorMessage(err, 'eval_err_load_students', this.msg),
      })),
    });
  }

  changeFillPage(page: number, questionnaireId: number, periodId: number): void {
    this.loadPeriodTargets(questionnaireId, periodId, page);
  }

  onPeriodChange(periodId: number, questionnaireId: number): void {
    this.fillState.update(s => ({
      ...s,
      selectedPeriodId: periodId,
      selectedUserId: null,
      editingEvaluationId: null,
      answers: {},
      page: 1,
    }));
    this.loadPeriodTargets(questionnaireId, periodId, 1);
  }

  selectUser(userId: number): void {
    const evaluated = this.fillState().evaluated.find(e => e.user.id === userId);
    if (evaluated) {
      const answers: Record<number, string> = {};
      for (const a of evaluated.answers) {
        answers[a.question_id] = a.value;
      }
      this.fillState.update(s => ({
        ...s,
        selectedUserId: userId,
        editingEvaluationId: evaluated.evaluation_id,
        answers,
      }));
    } else {
      this.fillState.update(s => ({
        ...s,
        selectedUserId: userId,
        editingEvaluationId: null,
        answers: {},
      }));
    }
  }

  setAnswer(questionId: number, value: string): void {
    this.fillState.update(s => ({
      ...s,
      answers: { ...s.answers, [questionId]: value },
    }));
  }

  save(): void {
    const state = this.fillState();

    if (state.selectedPeriodId === null) {
      this.fillState.update(s => ({ ...s, error: this.msg.get('eval_err_select_period') }));
      return;
    }

    if (state.selectedUserId === null) {
      this.fillState.update(s => ({ ...s, error: this.msg.get('eval_err_select_user') }));
      return;
    }

    const allQuestions = state.questionnaire?.sections.flatMap(s => s.questions) ?? [];
    const unanswered = allQuestions.filter(q => !state.answers[q.id]?.trim());
    if (unanswered && unanswered.length > 0) {
      this.fillState.update(s => ({ ...s, error: this.msg.get('eval_err_answer_all') }));
      return;
    }

    this.fillState.update(s => ({ ...s, saving: true, error: null }));

    const answers = Object.entries(state.answers).map(([questionId, value]) => ({
      question_id: Number(questionId),
      value,
    }));

    if (state.editingEvaluationId !== null) {
      this.api.updateEvaluation(state.editingEvaluationId, { answers }).subscribe({
        next: () => {
          this.fillState.update(s => ({
            ...s,
            saving: false,
            success: this.msg.get('eval_suc_evaluation_updated'),
            error: null,
          }));
        },
        error: err => this.fillState.update(s => ({
          ...s,
          saving: false,
          error: getErrorMessage(err, 'eval_err_update_evaluation', this.msg),
        })),
      });
    } else {
      this.api.createEvaluation({
        questionnaire_id: state.questionnaire!.id,
        target_user_id: state.selectedUserId,
        period_id: state.selectedPeriodId,
        answers,
      }).subscribe({
        next: () => {
          this.fillState.update(s => ({
            ...s,
            saving: false,
            success: this.msg.get('eval_suc_evaluation_saved'),
            error: null,
          }));
        },
        error: err => this.fillState.update(s => ({
          ...s,
          saving: false,
          error: getErrorMessage(err, 'eval_err_save_evaluation', this.msg),
        })),
      });
    }
  }

  clearFillMessages(): void {
    this.fillState.update(s => ({ ...s, error: null, success: null }));
  }

  openPlayerInfoModal(user: UserItem): void {
    this.fillState.update(s => ({ ...s, playerInfoLoading: true, playerInfoUser: user, playerInfoSpec: null }));

    this.api.getPlayerSpec(user.id).subscribe({
      next: spec => {
        this.fillState.update(s => ({
          ...s,
          playerInfoLoading: false,
          showPlayerInfoModal: true,
          playerInfoSpec: spec,
        }));
      },
      error: () => {
        this.fillState.update(s => ({
          ...s,
          playerInfoLoading: false,
          showPlayerInfoModal: true,
          playerInfoSpec: null,
        }));
      },
    });
  }

  closePlayerInfoModal(): void {
    this.fillState.update(s => ({
      ...s,
      showPlayerInfoModal: false,
      playerInfoUser: null,
      playerInfoSpec: null,
      playerInfoLoading: false,
    }));
  }

  resetFill(): void {
    this.fillState.update(s => ({
      ...s,
      questionnaire: null,
      periods: [],
      selectedPeriodId: null,
      unevaluated: [],
      evaluated: [],
      usersLoading: false,
      questionsLoading: false,
      selectedUserId: null,
      editingEvaluationId: null,
      answers: {},
      saving: false,
      error: null,
      success: null,
      page: 1,
      perPage: 20,
      total: 0,
      showPlayerInfoModal: false,
      playerInfoUser: null,
      playerInfoSpec: null,
      playerInfoLoading: false,
    }));
  }

  clearMessages(): void {
    this.listState.update(s => ({ ...s, error: null, success: null }));
  }
}
