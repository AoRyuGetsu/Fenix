import { Component, input, output, computed, inject } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction } from '../../../../shared/components/gtable/gtable.types';
import { PlayerTrend, QuestionnaireTeamStats } from '../../services/evaluation-stats-http.service';
import { MessageService } from '../../../../core/services/message.service';
@Component({
  selector: 'app-player-trend-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './player-trend-table.component.html',
})
export class PlayerTrendTableComponent {
  private msg = inject(MessageService);

  players = input<PlayerTrend[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  qStats = input<QuestionnaireTeamStats | null>(null);

  pageChange = output<number>();
  viewPlayer = output<PlayerTrend>();

  cols = computed<GtColumn<any>[]>(() => [
    { key: 'display_name', label: this.msg.get('es_lbl_player'), width: '60%' },
    {
      key: 'trend',
      label: this.msg.get('es_lbl_trend'),
      width: '25%',
      format: 'badge',
      badgeMap: {
        upward: { class: 'bg-success', label: this.msg.get('es_lbl_upward') },
        downward: { class: 'bg-danger', label: this.msg.get('es_lbl_downward') },
        stable: { class: 'bg-info', label: this.msg.get('es_lbl_stable') },
      },
    },
  ]);

  actions: GtAction<any>[] = [
    {
      key: 'view',
      icon: 'bi-bar-chart-line',
      label: this.msg.get('es_btn_see_analysis'),
      btnClass: 'btn-outline-info btn-sm',
    },
  ];

  playerData = computed(() => {
    return this.players().map(p => ({
      id: p.user.id,
      user: p.user,
      overall_averages: p.overall_averages,
      regression: p.regression,
      display_name: p.user.display_name,
      trend: p.regression?.trend ?? 'stable',
    }));
  });

  onAction(event: { key: string; item: any }): void {
    if (event.key === 'view') {
      const player = this.players().find(p => p.user.id === event.item.id);
      if (player) this.viewPlayer.emit(player);
    }
  }
}
