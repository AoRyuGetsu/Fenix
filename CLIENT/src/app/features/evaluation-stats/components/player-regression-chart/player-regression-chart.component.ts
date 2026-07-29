import { Component, input, computed } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { ChartOptions } from 'chart.js';
import { PeriodAverage, RegressionResult } from '../../services/evaluation-stats-http.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-player-regression-chart',
  standalone: true,
  imports: [BaseChartDirective, MessagePipe],
  templateUrl: './player-regression-chart.component.html',
})
export class PlayerRegressionChartComponent {
  title = input.required<string>();
  periodLabels = input.required<string[]>();
  periodAverages = input.required<PeriodAverage[]>();
  regression = input<RegressionResult | null>(null);
  showMeta = input(false);

  chartData = computed(() => {
    const labels = this.periodLabels();
    const avgs = this.periodAverages().map(a => a.average);
    const reg = this.regression();

    const datasets: any[] = [{
      label: 'Promedio',
      data: avgs,
      borderColor: '#0D1B5E',
      backgroundColor: 'rgba(13,27,94,0.1)',
      tension: 0,
      pointRadius: 6,
      pointBackgroundColor: '#0D1B5E',
    }];

    if (reg && labels.length >= 2) {
      const linePoints = labels.map((_, i) => {
        const x = i + 1;
        return reg.slope * x + reg.intercept;
      });
      datasets.push({
        label: 'Regresión lineal',
        data: linePoints,
        borderColor: '#F3E12D',
        borderDash: [5, 5],
        pointRadius: 0,
        tension: 0,
        borderWidth: 2,
      });
    }

    return { labels, datasets };
  });

  chartOptions: ChartOptions = {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
    },
    scales: {
      y: { beginAtZero: true },
    },
  };

  trendBadgeClass = computed(() => {
    const t = this.regression()?.trend;
    if (t === 'upward') return 'bg-success';
    if (t === 'downward') return 'bg-danger';
    return 'bg-info';
  });

  trendLabel = computed(() => {
    const t = this.regression()?.trend;
    if (t === 'upward') return 'es_lbl_upward';
    if (t === 'downward') return 'es_lbl_downward';
    return 'es_lbl_stable';
  });
}
