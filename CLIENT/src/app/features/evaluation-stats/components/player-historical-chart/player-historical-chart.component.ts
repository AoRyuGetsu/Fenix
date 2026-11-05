import { Component, computed, inject, input } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { Plugin } from 'chart.js';
import { PeriodAverage, RegressionResult } from '../../services/evaluation-stats-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { historialChartData, historialChartOptions, pointDescriptorPlugin } from '../../services/chart-config';

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

  private chartData = computed(() => historialChartData(this.averages(), this.regression(), this.t));

  private chartOptions = computed(() => historialChartOptions(this.averages(), this.t));

  private trendBadgeClass = computed(() => {
    const t = this.regression()?.trend;
    if (t === 'upward') return 'bg-success';
    if (t === 'downward') return 'bg-danger';
    return 'bg-info';
  });

  private descriptorPlugin = computed<Plugin<'scatter'>[]>(() => [pointDescriptorPlugin(this.t)]);

  vm = computed(() => ({
    averages: this.averages(),
    regression: this.regression(),
    trendBadgeClass: this.trendBadgeClass(),
    chartData: this.chartData(),
    chartOptions: this.chartOptions(),
    descriptorPlugin: this.descriptorPlugin(),
    t: this.t,
  }));
}