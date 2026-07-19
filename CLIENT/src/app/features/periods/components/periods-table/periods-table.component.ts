import { Component, input, output, computed } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction } from '../../../../shared/components/gtable/gtable.types';
import { PeriodItem } from '../../services/periods-http.service';

@Component({
  selector: 'app-periods-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './periods-table.component.html',
})
export class PeriodsTableComponent {
  items = input<PeriodItem[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  pageChange = output<number>();
  edit = output<PeriodItem>();
  delete = output<PeriodItem>();

  cols = computed<GtColumn<PeriodItem>[]>(() => [
    { key: 'name', label: 'Nombre', width: '30%' },
    { key: 'start_date', label: 'Fecha inicio', width: '25%', format: 'date' },
    { key: 'end_date', label: 'Fecha fin', width: '25%', format: 'date' },
  ]);

  actions: GtAction<PeriodItem>[] = [
    { key: 'edit', icon: 'bi-pencil', label: 'Editar', btnClass: 'btn-outline-primary btn-sm' },
    { key: 'delete', icon: 'bi-trash', label: 'Eliminar', btnClass: 'btn-outline-danger btn-sm' },
  ];

  onAction(event: { key: string; item: PeriodItem }): void {
    if (event.key === 'edit') this.edit.emit(event.item);
    if (event.key === 'delete') this.delete.emit(event.item);
  }
}
