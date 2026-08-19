import { Component, computed, inject, input, output } from '@angular/core';
import { QuestionItem } from '../../services/questionnaire-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-question-manager',
  standalone: true,
  templateUrl: './question-manager.component.html',
})
export class QuestionManagerComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  questions = input<QuestionItem[]>([]);
  loading = input(false);
  add = output<void>();
  edit = output<QuestionItem>();
  delete = output<number>();

  vm = computed(() => ({
    questions: this.questions(),
    loading: this.loading(),
    t: this.t,
  }));
}
