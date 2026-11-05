import { Component, computed, inject, input } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { Plugin } from 'chart.js';
import { Observation, RegressionResult } from '../../services/evaluation-stats-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { analysisChartData, analysisChartOptions, pointDescriptorPlugin } from '../../services/chart-config';

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

  private chartData = computed(() => analysisChartData(this.observations(), this.regression(), this.t));

  private chartOptions = computed(() => analysisChartOptions(this.observations(), this.t));

  private trendBadgeClass = computed(() => {
    const t = this.regression()?.trend;
    if (t === 'upward') return 'bg-success';
    if (t === 'downward') return 'bg-danger';
    return 'bg-info';
  });

  private descriptorPlugin = computed<Plugin<'scatter'>[]>(() => [pointDescriptorPlugin(this.t)]);

  vm = computed(() => ({
    observations: this.observations(),
    regression: this.regression(),
    trendBadgeClass: this.trendBadgeClass(),
    chartData: this.chartData(),
    chartOptions: this.chartOptions(),
    descriptorPlugin: this.descriptorPlugin(),
    t: this.t,
  }));
}
