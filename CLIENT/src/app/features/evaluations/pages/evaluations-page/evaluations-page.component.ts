import { Component, inject, computed, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { EvaluationsFacade } from '../../facades/evaluations.facade';
import { GtPagination } from '../../../../shared/components/gtable/gtable.types';
import { MyQuestionnaireItem } from '../../services/evaluations-http.service';
import { EvaluationsTableComponent } from '../../components/evaluations-table/evaluations-table.component';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-evaluations-page',
  standalone: true,
  imports: [EvaluationsTableComponent, MessagePipe],
  templateUrl: './evaluations-page.component.html',
})
export class EvaluationsPageComponent implements OnInit {
  private router = inject(Router);
  facade = inject(EvaluationsFacade);

  ngOnInit(): void {
    this.facade.load();
  }
  vm = this.facade.vm;

  pagination = computed<GtPagination>(() => ({
    page: this.vm().page,
    perPage: this.vm().perPage,
    total: this.vm().total,
  }));

  onFilter(term: string): void {
    this.facade.setSearchTerm(term);
  }

  onFill(item: MyQuestionnaireItem): void {
    this.router.navigate(['/evaluaciones', 'llenar', item.id]);
  }

  onChangePage(page: number): void {
    this.facade.changePage(page);
  }
}
