import { Component, computed, inject, input } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { ChartOptions } from 'chart.js';
import { PeriodAverage, RegressionResult } from '../../services/evaluation-stats-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-player-historical-chart',
  standalone: true,
  imports: [BaseChartDirective],
  templateUrl: './player-historical-chart.component.html',
})
export class PlayerHistoricalChartComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  averages = input.required<PeriodAverage[]>();
  regression = input<RegressionResult | null>(null);

  private chartData = computed(() => {
    const avgs = this.averages();
    const reg = this.regression();

    const datasets: any[] = [{
      label: this.t('es_lbl_avg_per_period'),
      data: avgs.map(a => ({ x: a.index, y: a.average })),
      backgroundColor: '#0D1B5E',
      borderColor: '#0D1B5E',
      pointRadius: 5,
      showLine: true,
      tension: 0.3,
    }];

    if (reg && avgs.length >= 2) {
      const xs = avgs.map(a => a.index);
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
            const raw = ctx.raw as { x: number; y: number };
            const a = this.averages().find(p => p.index === raw.x);
            const name = a ? a.period_name : `${raw.x}`;
            return this.t('es_lbl_avg_tooltip', { period: name, average: raw.y });
          },
        },
      },
    },
    scales: {
      x: {
        type: 'linear',
        title: { display: true, text: this.t('es_lbl_period') },
        ticks: {
          callback: (value: string | number) => {
            const a = this.averages().find(p => p.index === Number(value));
            return a ? a.period_name : value;
          },
        },
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
    averages: this.averages(),
    regression: this.regression(),
    trendBadgeClass: this.trendBadgeClass(),
    trendLabel: this.trendLabel(),
    chartData: this.chartData(),
    chartOptions: this.chartOptions,
    t: this.t,
  }));
}