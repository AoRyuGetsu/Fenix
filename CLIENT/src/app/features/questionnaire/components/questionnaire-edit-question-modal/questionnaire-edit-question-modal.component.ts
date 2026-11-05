import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { QuestionItem } from '../../services/questionnaire-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-questionnaire-edit-question-modal',
  standalone: true,
  templateUrl: './questionnaire-edit-question-modal.component.html',
})
export class QuestionnaireEditQuestionModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  private readonly defaultSelectionOptions = [
    { value: '1', labelKey: 'qnr_lbl_default_option_malo' },
    { value: '2', labelKey: 'qnr_lbl_default_option_regular' },
    { value: '3', labelKey: 'qnr_lbl_default_option_bueno' },
    { value: '4', labelKey: 'qnr_lbl_default_option_muy_bueno' },
    { value: '5', labelKey: 'qnr_lbl_default_option_excelente' },
  ];

  visible = input(false);
  loading = input(false);
  question = input<QuestionItem | null>(null);
  close = output<void>();
  save = output<{ id?: number; text: string; type: string }>();

  editText = signal('');
  editType = signal('selection');

  vm = computed(() => ({
    visible: this.visible(),
    loading: this.loading(),
    question: this.question(),
    editText: this.editText(),
    editType: this.editType(),
    isCreating: this.question() === null,
    selectionPreview: this.question() === null && this.editType() === 'selection'
      ? this.defaultSelectionOptions.map(o => ({ value: o.value, label: this.t(o.labelKey) }))
      : [],
    t: this.t,
  }));

  constructor() {
    effect(() => {
      if (this.visible()) {
        const q = this.question();
        if (q) {
          this.editText.set(q.text);
          this.editType.set(q.type);
        } else {
          this.editText.set('');
          this.editType.set('selection');
        }
      }
    });
  }

  onSave(): void {
    const data: { id?: number; text: string; type: string } = {
      text: this.editText(),
      type: this.editType(),
    };
    const q = this.question();
    if (q) data.id = q.id;
    this.save.emit(data);
  }
}