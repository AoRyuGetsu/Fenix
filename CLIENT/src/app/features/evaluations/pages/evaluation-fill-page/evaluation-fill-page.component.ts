import { Component, inject, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { EvaluationsFacade, EvaluatedStudent } from '../../facades/evaluations.facade';
import { QuestionCardComponent } from '../../components/question-card/question-card.component';
import { UserItem, PeriodItem } from '../../services/evaluations-http.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-evaluation-fill-page',
  standalone: true,
  imports: [QuestionCardComponent, MessagePipe],
  templateUrl: './evaluation-fill-page.component.html',
})
export class EvaluationFillPageComponent implements OnInit, OnDestroy {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  facade = inject(EvaluationsFacade);
  fillVm = this.facade.fillVm;

  private questionnaireId = 0;

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.router.navigate(['/evaluaciones']);
      return;
    }
    this.questionnaireId = id;
    this.facade.loadFillData(id);
  }

  ngOnDestroy(): void {
    this.facade.resetFill();
  }

  onPeriodChange(event: Event): void {
    const periodId = Number((event.target as HTMLSelectElement).value);
    if (periodId) {
      this.facade.onPeriodChange(periodId, this.questionnaireId);
    }
  }

  onFillPageChange(page: number): void {
    const periodId = this.fillVm().selectedPeriodId;
    if (periodId !== null) {
      this.facade.changeFillPage(page, this.questionnaireId, periodId);
    }
  }

  getUserDisplayName(user: UserItem): string {
    return user.display_name || user.email;
  }

  onUserSelect(userId: number): void {
    this.facade.selectUser(userId);
  }

  onAnswerChange(questionId: number, value: string): void {
    this.facade.setAnswer(questionId, value);
  }

  onSave(): void {
    this.facade.save();
  }

  getTotalPages(total: number, perPage: number): number {
    return Math.max(1, Math.ceil(total / perPage));
  }

  goBack(): void {
    this.router.navigate(['/evaluaciones']);
  }

  trackByQuestionId(_index: number, q: { id: number }): number {
    return q.id;
  }

  trackByUserId(_index: number, u: { id: number }): number {
    return u.id;
  }

  trackByEvaluatedId(_index: number, e: EvaluatedStudent): number {
    return e.evaluation_id;
  }
}
