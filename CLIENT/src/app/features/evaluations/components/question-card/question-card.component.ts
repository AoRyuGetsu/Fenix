import { Component, input, output } from '@angular/core';
import { QuestionItem } from '../../services/evaluations-http.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-question-card',
  standalone: true,
  imports: [MessagePipe],
  templateUrl: './question-card.component.html',
})
export class QuestionCardComponent {
  question = input.required<QuestionItem>();
  value = input<string>('');
  valueChange = output<string>();

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
