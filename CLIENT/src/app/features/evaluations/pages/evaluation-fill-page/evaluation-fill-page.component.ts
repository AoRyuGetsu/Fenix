import { Component, inject, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { EvaluationsFacade } from '../../facades/evaluations.facade';
import { QuestionCardComponent } from '../../components/question-card/question-card.component';
import { PlayerInfoModalComponent } from '../../components/player-info-modal/player-info-modal.component';
import { UserItem } from '../../services/evaluations-http.service';

@Component({
  selector: 'app-evaluation-fill-page',
  standalone: true,
  imports: [QuestionCardComponent, PlayerInfoModalComponent],
  templateUrl: './evaluation-fill-page.component.html',
})
export class EvaluationFillPageComponent implements OnInit, OnDestroy {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  facade = inject(EvaluationsFacade);
  vm = this.facade.fillVm;

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
    const periodId = this.vm().selectedPeriodId;
    if (periodId !== null) {
      this.facade.changeFillPage(page, this.questionnaireId, periodId);
    }
  }

  onUserSelect(userId: number): void {
    this.facade.selectUser(userId);
  }

  onAnswerChange(questionId: number, value: string): void {
    this.facade.setAnswer(questionId, value);
  }

  onObservacionesChange(event: Event): void {
    this.facade.setObservaciones((event.target as HTMLTextAreaElement).value);
  }

  onSave(): void {
    this.facade.save();
  }

  goBack(): void {
    this.router.navigate(['/evaluaciones']);
  }

  onShowFicha(user: UserItem): void {
    this.facade.openPlayerInfoModal(user);
  }

  onCloseFicha(): void {
    this.facade.closePlayerInfoModal();
  }

  trackByQuestionId(_index: number, q: { id: number }): number {
    return q.id;
  }

  trackByStudentId(_index: number, s: { user: { id: number } }): number {
    return s.user.id;
  }
}
