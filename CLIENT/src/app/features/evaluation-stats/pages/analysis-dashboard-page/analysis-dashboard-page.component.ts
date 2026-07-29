import { Component, OnInit, inject, computed } from '@angular/core';
import { Router } from '@angular/router';
import { AuthFacade } from '../../../../core/auth/facades/auth.facade';
import { EvaluationStatsFacade } from '../../facades/evaluation-stats.facade';
import { TrendDistributionChartComponent } from '../../components/trend-distribution-chart/trend-distribution-chart.component';
import { TeamAveragesChartComponent } from '../../components/team-averages-chart/team-averages-chart.component';
import { PlayerTrendTableComponent } from '../../components/player-trend-table/player-trend-table.component';
import { GtPagination } from '../../../../shared/components/gtable/gtable.types';
import { PlayerTrend } from '../../services/evaluation-stats-http.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-analysis-dashboard-page',
  standalone: true,
  imports: [
    MessagePipe,
    TrendDistributionChartComponent,
    TeamAveragesChartComponent,
    PlayerTrendTableComponent,
  ],
  templateUrl: './analysis-dashboard-page.component.html',
})
export class AnalysisDashboardPageComponent implements OnInit {
  auth = inject(AuthFacade);
  facade = inject(EvaluationStatsFacade);
  router = inject(Router);
  vm = this.facade.dashboardVm;

  ngOnInit(): void {
    if (this.auth.authRoles().includes('ENT')) {
      this.facade.loadMyTeam();
    } else {
      this.facade.loadTeams();
    }
  }

  trendData = computed(() => this.vm().teamStats?.trend_summary ?? { upward: 0, downward: 0, stable: 0 });

  teamChartLabels = computed(() => {
    const q = this.facade.currentQStats();
    return q?.team_averages.map(a => a.period_name) ?? [];
  });

  teamChartAverages = computed(() => {
    const q = this.facade.currentQStats();
    return q?.team_averages.map(a => a.average) ?? [];
  });

  pagination = computed<GtPagination>(() => {
    const players = this.facade.currentQStats()?.players ?? [];
    return { page: 1, perPage: 20, total: players.length };
  });

  onTeamChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    this.facade.selectTeam(Number(select.value));
  }

  onQuestionnaireChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    this.facade.selectQuestionnaire(Number(select.value));
  }

  onViewPlayer(player: PlayerTrend): void {
    this.router.navigate(['/analisis', 'jugador', player.user.id]);
  }
}
