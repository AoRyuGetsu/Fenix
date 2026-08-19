import { Component, input, output, computed, inject, signal, ViewChild, TemplateRef } from '@angular/core';
import { DatePipe } from '@angular/common';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { TeamsHttpService, TeamDetail, TeamItem } from '../../services/teams-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

interface TeamDisplayItem extends TeamItem {
  trainer_name: string;
}

@Component({
  selector: 'app-team-table',
  standalone: true,
  imports: [GTableComponent, DatePipe],
  templateUrl: './team-table.component.html',
})
export class TeamTableComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);
  private api = inject(TeamsHttpService);

  @ViewChild('detailTpl', { static: true }) detailTemplate!: TemplateRef<any>;

  items = input<TeamItem[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  pageChange = output<number>();
  edit = output<number>();
  delete = output<number>();
  expandedChange = output<number | null>();

  teamDetailMap = signal<Record<number, TeamDetail>>({});

  private displayItems = computed<TeamDisplayItem[]>(() =>
    this.items().map(item => ({
      ...item,
      trainer_name: item.trainer?.display_name || item.trainer?.email || '-',
    }))
  );

  private cols = computed<GtColumn<TeamDisplayItem>[]>(() => [
    { key: 'name', label: this.t('team_lbl_name'), width: '25%' },
    { key: 'code', label: this.t('team_lbl_code'), width: '15%' },
    { key: 'trainer_name', label: this.t('team_lbl_trainer'), width: '25%' },
    { key: 'player_count', label: this.t('team_lbl_player_count'), width: '10%' },
    { key: 'created_at', label: this.t('team_lbl_created'), width: '15%', format: 'date' },
  ]);

  private detailConfig = computed<GtDetailConfig>(() => ({
    template: this.detailTemplate,
  }));

  private actions: GtAction<TeamItem>[] = [
    { key: 'edit', icon: 'bi-pencil', label: this.t('cr_btn_edit'), btnClass: 'btn-outline-primary btn-sm' },
    { key: 'delete', icon: 'bi-trash', label: this.t('cr_btn_delete'), btnClass: 'btn-outline-danger btn-sm' },
  ];

  vm = computed(() => ({
    loading: this.loading(),
    cols: this.cols(),
    displayItems: this.displayItems(),
    pagination: this.pagination(),
    detailConfig: this.detailConfig(),
    actions: this.actions,
    teamDetailMap: this.teamDetailMap(),
    t: this.t,
  }));

  onExpand(id: number | null): void {
    this.expandedChange.emit(id);
    if (id === null) return;
    if (this.teamDetailMap()[id]) return;

    this.api.getById(id).subscribe({
      next: detail => {
        this.teamDetailMap.update(m => ({ ...m, [id]: detail }));
      },
    });
  }

  onAction(event: { key: string; item: TeamItem }): void {
    if (event.key === 'edit') this.edit.emit(event.item.id);
    if (event.key === 'delete') this.delete.emit(event.item.id);
  }
}
