import { Component, computed, inject, input, output } from '@angular/core';
import { QuestionItem } from '../../services/questionnaire-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-question-manager',
  standalone: true,
  templateUrl: './question-manager.component.html',
  styles: [`
    .qnr-move-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      padding: 0;
      border: none;
      background: transparent;
      color: var(--text-secondary);
      border-radius: 50%;
      transition: background-color 0.15s ease, color 0.15s ease;
    }
    .qnr-move-btn:hover {
      background-color: var(--primary);
      color: #FFFFFF;
    }
  `],
})
export class QuestionManagerComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  questions = input<QuestionItem[]>([]);
  loading = input(false);
  add = output<void>();
  edit = output<QuestionItem>();
  delete = output<number>();
  move = output<{ id: number; direction: 'up' | 'down' }>();

  vm = computed(() => ({
    questions: this.questions(),
    loading: this.loading(),
    t: this.t,
  }));
}
