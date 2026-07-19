import { Injectable, inject, signal } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { QuestionnaireHttpService, QuestionnaireItem, SectionItem, QuestionItem } from '../services/questionnaire-http.service';
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

  vm = this.state.asReadonly();

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
        error: getErrorMessage(err, 'Error al cargar cuestionarios'),
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
        this.state.update(s => ({ ...s, loading: false, showCreateModal: false, success: 'Cuestionario creado exitosamente' }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'Error al crear cuestionario'),
      })),
    });
  }

  updateQuestionnaire(id: number, data: { title?: string; description?: string; active?: boolean; responder_role_id?: number; target_role_id?: number }): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.updateQuestionnaire(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showUpdateModal: false, selectedItem: null, success: 'Cuestionario actualizado exitosamente' }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        loading: false,
        error: getErrorMessage(err, 'Error al actualizar cuestionario'),
      })),
    });
  }

  deleteQuestionnaire(id: number): void {
    if (!confirm('¿Está seguro de eliminar este cuestionario?')) return;

    this.api.deleteQuestionnaire(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Cuestionario eliminado exitosamente' }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al eliminar cuestionario'),
      })),
    });
  }

  loadDetailSections(id: number): void {
    this.state.update(s => ({ ...s, detailSectionsLoading: true, detailSections: [] }));
    this.api.getSections(id).subscribe({
      next: sections => this.state.update(s => ({
        ...s,
        detailSections: sections,
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
        this.state.update(s => ({ ...s, success: 'Sección agregada exitosamente' }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al crear sección'),
      })),
    });
  }

  updateSection(id: number, questionnaireId: number, data: { title?: string; description?: string; section_order?: number }): void {
    this.api.updateSection(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Sección actualizada exitosamente' }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al actualizar sección'),
      })),
    });
  }

  deleteSection(id: number, questionnaireId: number): void {
    if (!confirm('¿Está seguro de eliminar esta sección?')) return;

    this.api.deleteSection(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Sección eliminada exitosamente' }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al eliminar sección'),
      })),
    });
  }

  addQuestion(sectionId: number, questionnaireId: number, data: { text: string; type: string; question_order?: number; options?: string[] }): void {
    this.api.createQuestion(sectionId, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Pregunta agregada exitosamente' }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al crear pregunta'),
      })),
    });
  }

  updateQuestion(id: number, questionnaireId: number, data: { text?: string; type?: string; question_order?: number; options?: string[] }): void {
    this.api.updateQuestion(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Pregunta actualizada exitosamente' }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al actualizar pregunta'),
      })),
    });
  }

  deleteQuestion(id: number, questionnaireId: number): void {
    if (!confirm('¿Está seguro de eliminar esta pregunta?')) return;

    this.api.deleteQuestion(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Pregunta eliminada exitosamente' }));
        this.loadDetailSections(questionnaireId);
        this.clearSuccessAfterDelay();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al eliminar pregunta'),
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
