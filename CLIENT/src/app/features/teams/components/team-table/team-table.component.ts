import { Component, input, output, computed, inject, signal, ViewChild, TemplateRef } from '@angular/core';
import { DatePipe } from '@angular/common';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { TeamsHttpService, TeamDetail, TeamItem } from '../../services/teams-http.service';
import { MessageService } from '../../../../core/services/message.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

interface TeamDisplayItem extends TeamItem {
  trainer_name: string;
}

@Component({
  selector: 'app-team-table',
  standalone: true,
  imports: [GTableComponent, MessagePipe, DatePipe],
  templateUrl: './team-table.component.html',
})
export class TeamTableComponent {
  private msg = inject(MessageService);
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

  displayItems = computed<TeamDisplayItem[]>(() =>
    this.items().map(item => ({
      ...item,
      trainer_name: item.trainer?.display_name || item.trainer?.email || '-',
    }))
  );

  cols = computed<GtColumn<TeamDisplayItem>[]>(() => [
    { key: 'name', label: this.msg.get('team_lbl_name'), width: '25%' },
    { key: 'code', label: this.msg.get('team_lbl_code'), width: '15%' },
    { key: 'trainer_name', label: this.msg.get('team_lbl_trainer'), width: '25%' },
    { key: 'player_count', label: this.msg.get('team_lbl_player_count'), width: '10%' },
    { key: 'created_at', label: this.msg.get('team_lbl_created'), width: '15%', format: 'date' },
  ]);

  detailConfig = computed<GtDetailConfig>(() => ({
    template: this.detailTemplate,
  }));

  actions: GtAction<TeamItem>[] = [
    { key: 'edit', icon: 'bi-pencil', label: this.msg.get('cr_btn_edit'), btnClass: 'btn-outline-primary btn-sm' },
    { key: 'delete', icon: 'bi-trash', label: this.msg.get('cr_btn_delete'), btnClass: 'btn-outline-danger btn-sm' },
  ];

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
