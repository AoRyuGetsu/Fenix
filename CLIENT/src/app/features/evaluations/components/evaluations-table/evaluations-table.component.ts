import { Component, computed, inject, input, output } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction } from '../../../../shared/components/gtable/gtable.types';
import { MyQuestionnaireItem } from '../../services/evaluations-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-evaluations-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './evaluations-table.component.html',
})
export class EvaluationsTableComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  items = input<MyQuestionnaireItem[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  pageChange = output<number>();
  fill = output<MyQuestionnaireItem>();

  private cols = computed<GtColumn<MyQuestionnaireItem>[]>(() => [
    { key: 'title', label: this.t('eval_lbl_title'), width: '30%' },
    { key: 'description', label: this.t('eval_lbl_description'), width: '25%' },
    { key: 'target_role', label: this.t('eval_lbl_evaluates'), width: '15%' },
    { key: 'questions_count', label: this.t('eval_lbl_questions_count'), width: '10%' },
    { key: 'created_at', label: this.t('eval_lbl_created'), width: '15%', format: 'date' },
  ]);

  private actions: GtAction<MyQuestionnaireItem>[] = [
    { key: 'fill', icon: 'bi-pencil-square', label: this.t('eval_btn_fill'), btnClass: 'btn-outline-primary btn-sm' },
  ];

  vm = computed(() => ({
    loading: this.loading(),
    cols: this.cols(),
    items: this.items(),
    pagination: this.pagination(),
    actions: this.actions,
    t: this.t,
  }));

  onAction(event: { key: string; item: MyQuestionnaireItem }): void {
    if (event.key === 'fill') this.fill.emit(event.item);
  }
}
