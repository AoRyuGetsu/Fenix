import { Component, inject, computed, signal, OnInit } from '@angular/core';
import { QuestionnaireFacade } from '../../facades/questionnaire.facade';
import { GtPagination } from '../../../../shared/components/gtable/gtable.types';
import { SectionItem, QuestionItem } from '../../services/questionnaire-http.service';
import { QuestionnaireTableComponent } from '../../components/questionnaire-table/questionnaire-table.component';
import { QuestionnaireCreateModalComponent } from '../../components/questionnaire-create-modal/questionnaire-create-modal.component';
import { QuestionnaireUpdateModalComponent } from '../../components/questionnaire-update-modal/questionnaire-update-modal.component';
import { QuestionManagerComponent } from '../../components/question-manager/question-manager.component';
import { QuestionnaireEditQuestionModalComponent } from '../../components/questionnaire-edit-question-modal/questionnaire-edit-question-modal.component';

@Component({
  selector: 'app-questionnaire-page',
  standalone: true,
  imports: [
    QuestionnaireTableComponent,
    QuestionnaireCreateModalComponent,
    QuestionnaireUpdateModalComponent,
    QuestionManagerComponent,
    QuestionnaireEditQuestionModalComponent,
  ],
  templateUrl: './questionnaire-page.component.html',
})
export class QuestionnairePageComponent implements OnInit {
  facade = inject(QuestionnaireFacade);
  vm = this.facade.vm;

  ngOnInit(): void {
    this.facade.load();
    this.facade.loadRoles();
  }

  onFilter(term: string): void {
    this.facade.setSearchTerm(term);
  }
  showQuestionModal = signal(false);
  editingQuestion = signal<QuestionItem | null>(null);
  activeSectionId = signal<number | null>(null);
  expandedId = signal<number | null>(null);
  showSectionModal = signal(false);
  editingSection = signal<SectionItem | null>(null);
  sectionTitle = signal('');
  sectionDescription = signal('');

  pagination = computed<GtPagination>(() => ({
    page: this.vm().page,
    perPage: this.vm().perPage,
    total: this.vm().total,
  }));

  onExpandedChange(id: number | null): void {
    this.expandedId.set(id);
    if (id !== null) {
      this.facade.loadDetailSections(id);
    } else {
      this.facade.clearDetailSections();
    }
  }

  onAddSection(): void {
    this.editingSection.set(null);
    this.sectionTitle.set('');
    this.sectionDescription.set('');
    this.showSectionModal.set(true);
  }

  onEditSection(section: SectionItem): void {
    this.editingSection.set(section);
    this.sectionTitle.set(section.title);
    this.sectionDescription.set(section.description ?? '');
    this.showSectionModal.set(true);
  }

  onSaveSection(): void {
    const qId = this.expandedId();
    if (!qId || !this.sectionTitle().trim()) return;

    if (this.editingSection()) {
      this.facade.updateSection(this.editingSection()!.id, qId, {
        title: this.sectionTitle(),
        description: this.sectionDescription() || undefined,
      });
    } else {
      this.facade.addSection(qId, {
        title: this.sectionTitle(),
        description: this.sectionDescription() || undefined,
      });
    }
    this.showSectionModal.set(false);
  }

  onAddQuestion(sectionId: number): void {
    this.activeSectionId.set(sectionId);
    this.editingQuestion.set(null);
    this.showQuestionModal.set(true);
  }

  onEditQuestion(item: QuestionItem): void {
    this.editingQuestion.set(item);
    this.showQuestionModal.set(true);
  }

  onSaveQuestion(data: { id?: number; text: string; type: string; question_order?: number; options?: string[] }): void {
    const qId = this.expandedId();
    const sectionId = this.activeSectionId();
    if (!qId || !sectionId) return;

    if (data.id) {
      this.facade.updateQuestion(data.id, qId, {
        text: data.text,
        type: data.type,
        question_order: data.question_order,
        options: data.options,
      });
    } else {
      this.facade.addQuestion(sectionId, qId, {
        text: data.text,
        type: data.type,
        question_order: data.question_order,
        options: data.options,
      });
    }
    this.showQuestionModal.set(false);
  }
}
