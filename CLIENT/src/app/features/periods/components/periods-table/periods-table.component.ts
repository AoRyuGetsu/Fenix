import { Component, computed, inject, input, output } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction } from '../../../../shared/components/gtable/gtable.types';
import { PeriodItem } from '../../services/periods-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-periods-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './periods-table.component.html',
})
export class PeriodsTableComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  items = input<PeriodItem[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  pageChange = output<number>();
  edit = output<PeriodItem>();
  delete = output<PeriodItem>();

  private cols = computed<GtColumn<PeriodItem>[]>(() => [
    { key: 'name', label: this.t('per_lbl_name'), width: '30%' },
    { key: 'start_date', label: this.t('per_lbl_start_date'), width: '25%', format: 'date' },
    { key: 'end_date', label: this.t('per_lbl_end_date'), width: '25%', format: 'date' },
  ]);

  private actions: GtAction<PeriodItem>[] = [
    { key: 'edit', icon: 'bi-pencil', label: this.t('cr_btn_edit'), btnClass: 'btn-outline-primary btn-sm' },
    { key: 'delete', icon: 'bi-trash', label: this.t('cr_btn_delete'), btnClass: 'btn-outline-danger btn-sm' },
  ];

  vm = computed(() => ({
    loading: this.loading(),
    cols: this.cols(),
    items: this.items(),
    pagination: this.pagination(),
    actions: this.actions,
    t: this.t,
  }));

  onAction(event: { key: string; item: PeriodItem }): void {
    if (event.key === 'edit') this.edit.emit(event.item);
    if (event.key === 'delete') this.delete.emit(event.item);
  }
}
