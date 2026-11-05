import { Injectable, inject, signal, computed } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { QuestionnaireHttpService, QuestionnaireItem, SectionItem, QuestionItem, QuestionOptionPayload } from '../services/questionnaire-http.service';
import { RoleItem } from '../../../core/auth/services/auth-http.service';

export interface QuestionnaireState {
  items: QuestionnaireItem[];
  total: number;
  page: number;
  perPage: number;
  loading: boolean;
  error: string | null;
  success: string | null;
  showCreateModal: boolean;
  showUpdateModal: boolean;
  selectedItem: QuestionnaireItem | null;
  roles: RoleItem[];
  detailSections: SectionItem[];
  detailSectionsLoading: boolean;
  searchTerm: string;
}

@Injectable({ providedIn: 'root' })
export class QuestionnaireFacade {
  private api = inject(QuestionnaireHttpService);
  private msg = inject(MessageService);

  private state = signal<QuestionnaireState>({
    items: [],
    total: 0,
    page: 1,
    perPage: 20,
    loading: false,
    error: null,
    success: null,
    showCreateModal: false,
    showUpdateModal: false,
    selectedItem: null,
    roles: [],
    detailSections: [],
    detailSectionsLoading: false,
    searchTerm: '',
  });

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.state(), t: this.t }));

  load(page = 1): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.getQuestionnaires(page, this.state().perPage, this.state().searchTerm || undefined).subscribe({
      next: res => this.state.update(s => ({
        ...s,
        items: res.data,
        total: res.total,
        page: res.page,
        perPage: res.perPage,
        loading: false,
      })),
      error: err => this.state.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'qnr_err_load', this.msg),
      })),
    });
  }

  loadRoles(): void {
    const roles = this.api.getRolesFromCache();
    this.state.update(s => ({ ...s, roles }));
  }

  changePage(page: number): void {
    this.load(page);
  }

  setSearchTerm(term: string): void {
    this.state.update(s => ({ ...s, searchTerm: term, page: 1 }));
    this.load(1);
  }

  openCreateModal(): void {
    this.state.update(s => ({ ...s, showCreateModal: true }));
  }

  openUpdateModal(item: QuestionnaireItem): void {
    this.state.update(s => ({ ...s, showUpdateModal: true, selectedItem: item }));
  }

  closeModals(): void {
    this.state.update(s => ({ ...s, showCreateModal: false, showUpdateModal: false, selectedItem: null }));
  }

  createQuestionnaire(data: { title: string; description?: string; responder_role_id: number; target_role_id: number }): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.createQuestionnaire(data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showCreateModal: false, success: this.msg.get('qnr_suc_created') }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'qnr_err_create', this.msg),
      })),
    });
  }

  updateQuestionnaire(id: number, data: { title?: string; description?: string; active?: boolean; responder_role_id?: number; target_role_id?: number }): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.updateQuestionnaire(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showUpdateModal: false, selectedItem: null, success: this.msg.get('qnr_suc_updated') }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'qnr_err_update', this.msg),
      })),
    });
  }

  deleteQuestionnaire(id: number): void {
    if (!confirm(this.msg.get('qnr_cnf_delete'))) return;

    this.api.deleteQuestionnaire(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_deleted') }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_delete', this.msg),
      })),
    });
  }

  loadDetailSections(id: number): void {
    this.state.update(s => ({ ...s, detailSectionsLoading: true, detailSections: [] }));
    this.api.getSections(id).subscribe({
      next: sections => this.state.update(s => ({
        ...s,
        detailSections: sections,
        items: s.items.map(it => it.id === id
          ? { ...it, sections, questions_count: sections.reduce((n, sec) => n + sec.questions.length, 0) }
          : it),
        detailSectionsLoading: false,
      })),
      error: () => this.state.update(s => ({
        ...s,
        detailSectionsLoading: false,
      })),
    });
  }

  clearDetailSections(): void {
    this.state.update(s => ({ ...s, detailSections: [], detailSectionsLoading: false }));
  }

  addSection(questionnaireId: number, data: { title: string; description?: string; section_order?: number }): void {
    this.api.createSection(questionnaireId, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_section_added') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_create_section', this.msg),
      })),
    });
  }

  updateSection(id: number, questionnaireId: number, data: { title?: string; description?: string; section_order?: number }): void {
    this.api.updateSection(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_section_updated') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_update_section', this.msg),
      })),
    });
  }

  deleteSection(id: number, questionnaireId: number): void {
    if (!confirm(this.msg.get('qnr_cnf_delete_section'))) return;

    this.api.deleteSection(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_section_deleted') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_delete_section', this.msg),
      })),
    });
  }

  addQuestion(sectionId: number, questionnaireId: number, data: { text: string; type: string; question_order?: number; options?: QuestionOptionPayload[] }): void {
    this.api.createQuestion(sectionId, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_question_added') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_create_question', this.msg),
      })),
    });
  }

  updateQuestion(id: number, questionnaireId: number, data: { text?: string; type?: string; question_order?: number; options?: QuestionOptionPayload[] }): void {
    this.api.updateQuestion(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_question_updated') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_update_question', this.msg),
      })),
    });
  }

  deleteQuestion(id: number, questionnaireId: number): void {
    if (!confirm(this.msg.get('qnr_cnf_delete_question'))) return;

    this.api.deleteQuestion(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_question_deleted') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_delete_question', this.msg),
      })),
    });
  }

  moveQuestion(id: number, questionnaireId: number, direction: 'up' | 'down'): void {
    this.api.moveQuestion(id, direction).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: this.msg.get('qnr_suc_question_updated') }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'qnr_err_update_question', this.msg),
      })),
    });
  }

  clearMessages(): void {
    this.state.update(s => ({ ...s, error: null, success: null }));
  }

  private clearSuccessAfterDelay(): void {
    setTimeout(() => {
      this.state.update(s => ({ ...s, success: null }));
    }, 3000);
  }
}
