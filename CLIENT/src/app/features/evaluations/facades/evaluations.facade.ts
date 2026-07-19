import { Injectable, inject, signal } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService } from '../../../core/services/message.service';
import {
  EvaluationsHttpService,
  MyQuestionnaireItem,
  QuestionnaireDetail,
  UserItem,
  PeriodItem,
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
  });

  vm = this.listState.asReadonly();
  fillVm = this.fillState.asReadonly();

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
        error: getErrorMessage(err, this.msg.get('eval_err_load_evaluations')),
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
        error: getErrorMessage(err, this.msg.get('eval_err_load_periods')),
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
        error: getErrorMessage(err, this.msg.get('eval_err_load_questionnaire')),
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
        error: getErrorMessage(err, this.msg.get('eval_err_load_students')),
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
          error: getErrorMessage(err, this.msg.get('eval_err_update_evaluation')),
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
          error: getErrorMessage(err, this.msg.get('eval_err_save_evaluation')),
        })),
      });
    }
  }

  clearFillMessages(): void {
    this.fillState.update(s => ({ ...s, error: null, success: null }));
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
    }));
  }

  clearMessages(): void {
    this.listState.update(s => ({ ...s, error: null, success: null }));
  }
}
