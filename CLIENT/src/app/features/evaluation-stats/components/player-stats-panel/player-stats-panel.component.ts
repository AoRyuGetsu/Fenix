import { Component, computed, inject, input } from '@angular/core';
import { EvaluationStatsFacade } from '../../facades/evaluation-stats.facade';
import { PlayerRegressionChartComponent } from '../player-regression-chart/player-regression-chart.component';
import { PlayerHistoricalChartComponent } from '../player-historical-chart/player-historical-chart.component';

@Component({
  selector: 'app-player-stats-panel',
  standalone: true,
  imports: [PlayerRegressionChartComponent, PlayerHistoricalChartComponent],
  templateUrl: './player-stats-panel.component.html',
})
export class PlayerStatsPanelComponent {
  facade = inject(EvaluationStatsFacade);

  userId = input<number | null>(null);
  playerName = input<string | null>(null);

  selectedQStats = computed(() => {
    const q = this.facade.currentPlayerQStats();
    if (!q) return null;
    return q;
  });

  overallObservations = computed(() => this.selectedQStats()?.overall.observations ?? []);

  currentQuestionStats = computed(() => this.facade.currentQuestion());

  vm = computed(() => {
    const s = this.facade.playerVm();
    return {
      ...s,
      playerName: this.playerName(),
      playerQuestionnaireOptions: this.facade.playerQuestionnaireOptions(),
      selectedQStats: this.selectedQStats(),
      overallObservations: this.overallObservations(),
      currentQuestionStats: this.currentQuestionStats(),
    };
  });

  onQuestionnaireChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    this.facade.selectPlayerQuestionnaire(Number(select.value));
  }

  onSetViewMode(mode: 'analysis' | 'historial'): void {
    this.facade.setViewMode(mode);
  }

  onPrevQuestion(): void {
    this.facade.previousQuestion();
  }

  onNextQuestion(): void {
    this.facade.nextQuestion();
  }
}
