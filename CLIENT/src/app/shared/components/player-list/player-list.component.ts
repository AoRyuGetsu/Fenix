import { Component, computed, inject, input, output } from '@angular/core';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { GtPagination } from '../gtable/gtable.types';
import { PlayerListItem } from './player-list.types';

@Component({
  selector: 'app-player-list',
  standalone: true,
  templateUrl: './player-list.component.html',
})
export class PlayerListComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  items = input<PlayerListItem[]>([]);
  activeId = input<number | null>(null);
  loading = input(false);
  pagination = input<GtPagination | null>(null);
  emptyLabel = input('es_lbl_no_data');
  searchPlaceholder = input<string | null>(null);
  searchValue = input('');

  selectPlayer = output<number>();
  pageChange = output<number>();
  searchChange = output<string>();

  private totalPages = computed(() => {
    const p = this.pagination();
    if (!p) return 1;
    return Math.ceil(p.total / p.perPage) || 1;
  });

  private pages = computed(() => {
    if (!this.pagination()) return [];
    return Array.from({ length: this.totalPages() }, (_, i) => i + 1);
  });

  vm = computed(() => ({
    items: this.items(),
    activeId: this.activeId(),
    loading: this.loading(),
    pagination: this.pagination(),
    emptyLabel: this.emptyLabel(),
    searchPlaceholder: this.searchPlaceholder(),
    searchValue: this.searchValue(),
    pages: this.pages(),
    totalPages: this.totalPages(),
    t: this.t,
  }));

  onSelect(id: number): void {
    this.selectPlayer.emit(id);
  }

  onSearchInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value.trim();
    this.searchChange.emit(value);
  }

  onSearchButtonClick(input: HTMLInputElement): void {
    this.searchChange.emit(input.value.trim());
  }
}
