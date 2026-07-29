import { Component, OnInit, inject, computed } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { EvaluationStatsFacade } from '../../facades/evaluation-stats.facade';
import { PlayerRegressionChartComponent } from '../../components/player-regression-chart/player-regression-chart.component';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-player-analysis-page',
  standalone: true,
  imports: [MessagePipe, PlayerRegressionChartComponent],
  templateUrl: './player-analysis-page.component.html',
})
export class PlayerAnalysisPageComponent implements OnInit {
  facade = inject(EvaluationStatsFacade);
  route = inject(ActivatedRoute);
  router = inject(Router);
  vm = this.facade.playerVm;

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.router.navigate(['/analisis']);
      return;
    }
    this.facade.loadPlayerStats(id);
  }

  playerName = computed(() => this.vm().playerStats?.target_user.display_name ?? '');

  selectedQStats = computed(() => {
    const q = this.facade.currentPlayerQStats();
    if (!q) return null;
    return q;
  });

  overallLabels = computed(() => {
    const q = this.selectedQStats();
    return q?.overall.averages.map(a => a.period_name) ?? [];
  });

  overallAverages = computed(() => {
    const q = this.selectedQStats();
    return q?.overall.averages ?? [];
  });

  onQuestionnaireChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    this.facade.selectPlayerQuestionnaire(Number(select.value));
  }

  onBack(): void {
    this.router.navigate(['/analisis']);
  }
}
