import { Component, input, computed } from '@angular/core';
import { BaseChartDirective } from 'ng2-charts';
import { ChartOptions } from 'chart.js';
import { TrendCounts } from '../../services/evaluation-stats-http.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-trend-distribution-chart',
  standalone: true,
  imports: [BaseChartDirective, MessagePipe],
  templateUrl: './trend-distribution-chart.component.html',
})
export class TrendDistributionChartComponent {
  data = input.required<TrendCounts>();
  title = input.required<string>();

  chartData = computed(() => ({
    labels: [this.data().upward, this.data().downward, this.data().stable],
    datasets: [{
      data: [this.data().upward, this.data().downward, this.data().stable],
      backgroundColor: ['#43a047', '#e53935', '#039be5'],
      borderWidth: 0,
    }],
  }));

  chartOptions: ChartOptions = {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: { enabled: true },
    },
  };
}
