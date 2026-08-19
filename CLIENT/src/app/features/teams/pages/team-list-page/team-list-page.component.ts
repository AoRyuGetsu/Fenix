import { Component, inject, computed, OnInit } from '@angular/core';
import { TeamsFacade } from '../../facades/teams.facade';
import { TeamTableComponent } from '../../components/team-table/team-table.component';
import { TeamFormModalComponent } from '../../components/team-form-modal/team-form-modal.component';
import { GtPagination } from '../../../../shared/components/gtable/gtable.types';
import { MessageService } from '../../../../core/services/message.service';

@Component({
  selector: 'app-team-list-page',
  standalone: true,
  imports: [TeamTableComponent, TeamFormModalComponent],
  templateUrl: './team-list-page.component.html',
})
export class TeamListPageComponent implements OnInit {
  facade = inject(TeamsFacade);
  vm = this.facade.vm;
  private msg = inject(MessageService);

  ngOnInit(): void {
    this.facade.load();
  }

  pagination = computed<GtPagination>(() => ({
    page: this.vm().page,
    perPage: this.vm().perPage,
    total: this.vm().total,
  }));

  onFilter(term: string): void {
    this.facade.setSearchTerm(term);
  }

  onPageChange(page: number): void {
    this.facade.changePage(page);
  }

  onEdit(id: number): void {
    this.facade.openEdit(id);
  }

  onDelete(id: number): void {
    if (confirm(this.msg.get('team_cnf_delete'))) {
      this.facade.delete(id);
    }
  }

  onNew(): void {
    this.facade.openCreate();
  }
}
