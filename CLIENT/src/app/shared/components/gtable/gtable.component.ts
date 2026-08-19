import { Component, input, output, signal, computed, inject } from '@angular/core';
import { DatePipe, NgTemplateOutlet } from '@angular/common';
import { GtColumn, GtAction, GtDetailConfig, GtPagination } from './gtable.types';
import { MessageService } from '../../../core/services/message.service';

@Component({
  selector: 'app-gtable',
  imports: [DatePipe, NgTemplateOutlet],
  templateUrl: './gtable.component.html',
})
export class GTableComponent<T extends { id: number }> {
  protected msg = inject(MessageService);

  columns    = input<GtColumn<T>[]>([]);
  data       = input<T[]>([]);
  pagination = input<GtPagination>({ page: 1, perPage: 20, total: 0 });
  loading    = input(false);
  emptyMsg   = input('');

  detail     = input<GtDetailConfig | null>(null);
  actions    = input<GtAction<T>[]>([]);

  pageChange   = output<number>();
  actionClick  = output<{ key: string; item: T }>();
  expandedChange = output<number | null>();

  expandedId = signal<number | null>(null);

  totalPages = computed(() => {
    const p = this.pagination();
    return Math.ceil(p.total / p.perPage) || 1;
  });

  pages = computed(() => {
    const tp = this.totalPages();
    return Array.from({ length: tp }, (_, i) => i + 1);
  });

  toggleExpand(id: number): void {
    const next = this.expandedId() === id ? null : id;
    this.expandedId.set(next);
    this.expandedChange.emit(next);
  }

  isExpanded(id: number): boolean {
    return this.expandedId() === id;
  }

  colspan(): number {
    let n = this.columns().length;
    if (this.detail() !== null) n++;
    if (this.actions().length > 0) n++;
    return n;
  }

  cellValue(item: T, key: string): any {
    return (item as any)[key] ?? null;
  }

  identFn(_index: number, item: T): number {
    return item.id;
  }
}
