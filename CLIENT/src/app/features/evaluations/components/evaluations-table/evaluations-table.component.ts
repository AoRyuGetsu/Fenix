import { Component, input, output, computed, inject } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction } from '../../../../shared/components/gtable/gtable.types';
import { MyQuestionnaireItem } from '../../services/evaluations-http.service';
import { MessageService } from '../../../../core/services/message.service';

@Component({
  selector: 'app-evaluations-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './evaluations-table.component.html',
})
export class EvaluationsTableComponent {
  private msg = inject(MessageService);

  items = input<MyQuestionnaireItem[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  pageChange = output<number>();
  fill = output<MyQuestionnaireItem>();

  cols = computed<GtColumn<MyQuestionnaireItem>[]>(() => [
    { key: 'title', label: this.msg.get('eval_lbl_title'), width: '30%' },
    { key: 'description', label: this.msg.get('eval_lbl_description'), width: '25%' },
    { key: 'target_role', label: this.msg.get('eval_lbl_evaluates'), width: '15%' },
    { key: 'questions_count', label: this.msg.get('eval_lbl_questions_count'), width: '10%' },
    { key: 'created_at', label: this.msg.get('eval_lbl_created'), width: '15%', format: 'date' },
  ]);

  actions: GtAction<MyQuestionnaireItem>[] = [
    { key: 'fill', icon: 'bi-pencil-square', label: this.msg.get('eval_btn_fill'), btnClass: 'btn-outline-primary btn-sm' },
  ];

  onAction(event: { key: string; item: MyQuestionnaireItem }): void {
    if (event.key === 'fill') this.fill.emit(event.item);
  }
}
