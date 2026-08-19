import { Component, computed, inject, input, output, signal, TemplateRef, ViewChild } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { QuestionnaireItem } from '../../services/questionnaire-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-questionnaire-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './questionnaire-table.component.html',
})
export class QuestionnaireTableComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  items = input<QuestionnaireItem[]>([]);
  loading = input(false);
  pagination = input.required<GtPagination>();
  detailTemplate = input<TemplateRef<{ $implicit: QuestionnaireItem }> | null>(null);
  pageChange = output<number>();
  expandedChange = output<number | null>();
  edit = output<QuestionnaireItem>();
  delete = output<QuestionnaireItem>();

  @ViewChild('countTpl') set countTpl(v: TemplateRef<{ $implicit: QuestionnaireItem }>) {
    this.countTplSignal.set(v);
  }

  private countTplSignal = signal<TemplateRef<{ $implicit: QuestionnaireItem }> | null>(null);

  private cols = computed<GtColumn<QuestionnaireItem>[]>(() => {
    const tpl = this.countTplSignal();
    const result: GtColumn<QuestionnaireItem>[] = [
      { key: 'title', label: this.t('qnr_lbl_title'), width: '30%' },
      { key: 'active', label: this.t('qnr_lbl_active'), width: '10%', format: 'boolean' },
      { key: 'responder_role', label: this.t('qnr_lbl_responds'), width: '15%' },
      { key: 'target_role', label: this.t('qnr_lbl_evaluates'), width: '15%' },
      { key: 'questions_count', label: this.t('qnr_lbl_questions'), width: '8%', format: 'template', template: tpl ? (tpl as any) : undefined },
      { key: 'created_at', label: this.t('qnr_lbl_created'), width: '15%', format: 'date' },
    ];
    return result;
  });

  private actions: GtAction<QuestionnaireItem>[] = [
    { key: 'edit', icon: 'bi-pencil', label: this.t('cr_btn_edit'), btnClass: 'btn-outline-info btn-sm' },
    { key: 'delete', icon: 'bi-trash', label: this.t('cr_btn_delete'), btnClass: 'btn-outline-danger btn-sm' },
  ];

  private detailConfig = computed<GtDetailConfig | null>(() => {
    const tpl = this.detailTemplate();
    return tpl ? { template: tpl } : null;
  });

  vm = computed(() => ({
    cols: this.cols(),
    items: this.items(),
    pagination: this.pagination(),
    loading: this.loading(),
    actions: this.actions,
    detailConfig: this.detailConfig(),
    t: this.t,
  }));

  onAction(event: { key: string; item: QuestionnaireItem }): void {
    if (event.key === 'edit') this.edit.emit(event.item);
    if (event.key === 'delete') this.delete.emit(event.item);
  }
}
