import { Component, input, computed } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { ChartOptions } from 'chart.js';
import { RegressionResult } from '../../services/evaluation-stats-http.service';
@Component({
  selector: 'app-team-averages-chart',
  standalone: true,
  imports: [BaseChartDirective],
  templateUrl: './team-averages-chart.component.html',
})
export class TeamAveragesChartComponent {
  periodLabels = input.required<string[]>();
  averages = input.required<number[]>();
  regression = input<RegressionResult | null>(null);
  title = input.required<string>();

  chartData = computed(() => {
    const labels = this.periodLabels();
    const avgs = this.averages();
    const reg = this.regression();

    const datasets: any[] = [{
      label: 'Promedio',
      data: avgs,
      borderColor: '#0D1B5E',
      backgroundColor: 'rgba(13,27,94,0.1)',
      tension: 0,
      pointRadius: 5,
      pointBackgroundColor: '#0D1B5E',
    }];

    if (reg && labels.length >= 2) {
      const linePoints = labels.map((_, i) => {
        const x = i + 1;
        return reg.slope * x + reg.intercept;
      });
      datasets.push({
        label: 'Regresión',
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
}
