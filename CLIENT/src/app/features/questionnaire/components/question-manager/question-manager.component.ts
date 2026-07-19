import { Component, input, output } from '@angular/core';
import { QuestionItem } from '../../services/questionnaire-http.service';

@Component({
  selector: 'app-question-manager',
  standalone: true,
  templateUrl: './question-manager.component.html',
})
export class QuestionManagerComponent {
  questions = input<QuestionItem[]>([]);
  loading = input(false);
  add = output<void>();
  edit = output<QuestionItem>();
  delete = output<number>();
}
