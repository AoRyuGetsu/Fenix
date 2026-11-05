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

  private scaleHint = computed(() => {
    const options = this.question().options;
    if (!options || options.length === 0) return null;
    return options.map(opt => `${opt.option_text}=${opt.label || opt.option_text}`).join(', ');
  });

  vm = computed(() => ({
    question: this.question(),
    value: this.value(),
    scaleHint: this.scaleHint(),
    t: this.t,
  }));

  onInput(event: Event): void {
    this.valueChange.emit((event.target as HTMLInputElement).value);
  }

  onSelect(event: Event): void {
    this.valueChange.emit((event.target as HTMLSelectElement).value);
  }

  trackByOptionId(_index: number, option: { id: number }): number {
    return option.id;
  }
}
