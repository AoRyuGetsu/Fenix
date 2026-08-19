import { Component, inject, computed, OnInit } from '@angular/core';
import { RepresentadosFacade } from '../../facades/representados.facade';
import { GtPagination } from '../../../../shared/components/gtable/gtable.types';
import { PlayerListComponent } from '../../../../shared/components/player-list/player-list.component';
import { PlayerDetailCardsComponent } from '../../../../shared/components/player-detail/player-detail-cards.component';

@Component({
  selector: 'app-representados-page',
  standalone: true,
  imports: [PlayerListComponent, PlayerDetailCardsComponent],
  templateUrl: './representados-page.component.html',
})
export class RepresentadosPageComponent implements OnInit {
  facade = inject(RepresentadosFacade);
  vm = this.facade.vm;

  ngOnInit(): void {
    this.facade.load();
  }

  listItems = computed(() =>
    this.vm().items.map(i => ({ id: i.id, display_name: i.display_name })),
  );

  pagination = computed<GtPagination>(() => ({
    page: this.vm().page,
    perPage: this.vm().perPage,
    total: this.vm().total,
  }));

  onSelect(id: number): void {
    this.facade.select(id);
  }

  onChangePage(page: number): void {
    this.facade.changePage(page);
  }

  onSearch(term: string): void {
    this.facade.setSearch(term);
  }
}