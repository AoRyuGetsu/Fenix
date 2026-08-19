import { Component, computed, inject, input } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { ChartOptions } from 'chart.js';
import { Observation, RegressionResult } from '../../services/evaluation-stats-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-player-regression-chart',
  standalone: true,
  imports: [BaseChartDirective],
  templateUrl: './player-regression-chart.component.html',
})
export class PlayerRegressionChartComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  observations = input.required<Observation[]>();
  regression = input<RegressionResult | null>(null);

  private chartData = computed(() => {
    const obs = this.observations();
    const reg = this.regression();

    const datasets: any[] = [{
      label: this.t('es_lbl_observations'),
      data: obs.map(o => ({ x: o.x_days, y: o.value })),
      backgroundColor: '#0D1B5E',
      borderColor: '#0D1B5E',
      pointRadius: 5,
      showLine: false,
    }];

    if (reg && obs.length >= 2) {
      const xs = obs.map(o => o.x_days);
      const xMin = Math.min(...xs);
      const xMax = Math.max(...xs);
      datasets.push({
        label: this.t('es_lbl_regression'),
        data: [
          { x: xMin, y: reg.slope * xMin + reg.intercept },
          { x: xMax, y: reg.slope * xMax + reg.intercept },
        ],
        borderColor: '#F3E12D',
        backgroundColor: '#F3E12D',
        borderDash: [5, 5],
        pointRadius: 0,
        showLine: true,
        tension: 0,
        borderWidth: 2,
      });
    }

    return { datasets };
  });

  private chartOptions: ChartOptions<'scatter'> = {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: ctx => {
            const obs = this.observations()[ctx.dataIndex];
            const point = ctx.raw as { x: number; y: number };
            if (obs) {
              return this.t('es_lbl_obs_tooltip', { date: obs.evaluated_at, value: point.y, residual: obs.residual });
            }
            return `${point.y}`;
          },
        },
      },
    },
    scales: {
      x: {
        type: 'linear',
        title: { display: true, text: this.t('es_lbl_days') },
      },
      y: { beginAtZero: true },
    },
  };

  private trendBadgeClass = computed(() => {
    const t = this.regression()?.trend;
    if (t === 'upward') return 'bg-success';
    if (t === 'downward') return 'bg-danger';
    return 'bg-info';
  });

  private trendLabel = computed(() => {
    const t = this.regression()?.trend;
    if (t === 'upward') return 'es_lbl_upward';
    if (t === 'downward') return 'es_lbl_downward';
    return 'es_lbl_stable';
  });

  vm = computed(() => ({
    observations: this.observations(),
    regression: this.regression(),
    trendBadgeClass: this.trendBadgeClass(),
    trendLabel: this.trendLabel(),
    chartData: this.chartData(),
    chartOptions: this.chartOptions,
    t: this.t,
  }));
}
