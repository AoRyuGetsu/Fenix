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

  visible = input(false);
  loading = input(false);
  question = input<QuestionItem | null>(null);
  close = output<void>();
  save = output<{ id?: number; text: string; type: string; question_order?: number; options?: string[] }>();

  editText = signal('');
  editType = signal('text');
  editOptions = signal('');
  editOrder = signal(0);

  vm = computed(() => ({
    visible: this.visible(),
    loading: this.loading(),
    question: this.question(),
    editText: this.editText(),
    editType: this.editType(),
    editOptions: this.editOptions(),
    editOrder: this.editOrder(),
    t: this.t,
  }));

  constructor() {
    effect(() => {
      if (this.visible()) {
        const q = this.question();
        if (q) {
          this.editText.set(q.text);
          this.editType.set(q.type);
          this.editOrder.set(q.question_order);
          this.editOptions.set(q.options?.map(o => o.option_text).join('\n') ?? '');
        } else {
          this.editText.set('');
          this.editType.set('text');
          this.editOptions.set('');
          this.editOrder.set(0);
        }
      }
    });
  }

  onSave(): void {
    const data: { id?: number; text: string; type: string; question_order?: number; options?: string[] } = {
      text: this.editText(),
      type: this.editType(),
      question_order: this.editOrder(),
    };
    if (this.editType() === 'selection' && this.editOptions()) {
      data.options = this.editOptions().split('\n').map(s => s.trim()).filter(s => s);
    }
    const q = this.question();
    if (q) data.id = q.id;
    this.save.emit(data);
  }
}
