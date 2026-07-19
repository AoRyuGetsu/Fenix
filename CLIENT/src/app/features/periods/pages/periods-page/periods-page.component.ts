import { Component, inject, computed, OnInit } from '@angular/core';
import { PeriodsFacade } from '../../facades/periods.facade';
import { GtPagination } from '../../../../shared/components/gtable/gtable.types';
import { PeriodItem, PeriodPayload } from '../../services/periods-http.service';
import { PeriodsTableComponent } from '../../components/periods-table/periods-table.component';
import { PeriodFormModalComponent } from '../../components/period-form-modal/period-form-modal.component';

@Component({
  selector: 'app-periods-page',
  standalone: true,
  imports: [PeriodsTableComponent, PeriodFormModalComponent],
  templateUrl: './periods-page.component.html',
})
export class PeriodsPageComponent implements OnInit {
  facade = inject(PeriodsFacade);

  ngOnInit(): void {
    this.facade.load();
  }
  vm = this.facade.vm;

  pagination = computed<GtPagination>(() => ({
    page: this.vm().page,
    perPage: this.vm().perPage,
    total: this.vm().total,
  }));

  onNew(): void {
    this.facade.openCreate();
  }

  onEdit(item: PeriodItem): void {
    this.facade.openEdit(item);
  }

  onDelete(item: PeriodItem): void {
    this.facade.deletePeriod(item.id);
  }

  onSave(payload: PeriodPayload): void {
    this.facade.save(payload);
  }

  onChangePage(page: number): void {
    this.facade.changePage(page);
  }

  onCloseForm(): void {
    this.facade.closeForm();
  }

  onFilter(name: string): void {
    this.facade.setFilter(name);
  }

  clearMessages(): void {
    this.facade.clearMessages();
  }
}
