import { Component, computed, inject, input, output } from '@angular/core';
import { QuestionItem } from '../../services/evaluations-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-question-card',
  standalone: true,
  templateUrl: './question-card.component.html',
})
export class QuestionCardComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  question = input.required<QuestionItem>();
  value = input<string>('');
  valueChange = output<string>();

  vm = computed(() => ({
    question: this.question(),
    value: this.value(),
    t: this.t,
  }));

  onInput(event: Event): void {
    this.valueChange.emit((event.target as HTMLInputElement).value);
  }

  onTextarea(event: Event): void {
    this.valueChange.emit((event.target as HTMLTextAreaElement).value);
  }

  onSelect(event: Event): void {
    this.valueChange.emit((event.target as HTMLSelectElement).value);
  }

  onOptionSelect(optionText: string): void {
    this.valueChange.emit(optionText);
  }

  trackByOptionId(_index: number, option: { id: number }): number {
    return option.id;
  }
}
