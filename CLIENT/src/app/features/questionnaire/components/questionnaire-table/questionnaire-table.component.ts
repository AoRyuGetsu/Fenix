import { Component, input, output, computed, signal, TemplateRef, ViewChild } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtPagination, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { QuestionnaireItem } from '../../services/questionnaire-http.service';

@Component({
  selector: 'app-questionnaire-table',
  standalone: true,
  imports: [GTableComponent],
  templateUrl: './questionnaire-table.component.html',
})
export class QuestionnaireTableComponent {
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

  cols = computed<GtColumn<QuestionnaireItem>[]>(() => {
    const tpl = this.countTplSignal();
    const result: GtColumn<QuestionnaireItem>[] = [
      { key: 'title', label: 'Título', width: '30%' },
      { key: 'active', label: 'Activo', width: '10%', format: 'boolean' },
      { key: 'responder_role', label: 'Responde', width: '15%' },
      { key: 'target_role', label: 'Evalúa a', width: '15%' },
      { key: 'questions_count', label: 'Preguntas', width: '8%', format: 'template', template: tpl ? (tpl as any) : undefined },
      { key: 'created_at', label: 'Creado', width: '15%', format: 'date' },
    ];
    return result;
  });

  actions: GtAction<QuestionnaireItem>[] = [
    { key: 'edit', icon: 'bi-pencil', label: 'Editar', btnClass: 'btn-outline-info btn-sm' },
    { key: 'delete', icon: 'bi-trash', label: 'Eliminar', btnClass: 'btn-outline-danger btn-sm' },
  ];

  detailConfig = computed<GtDetailConfig | null>(() => {
    const tpl = this.detailTemplate();
    return tpl ? { template: tpl } : null;
  });

  onAction(event: { key: string; item: QuestionnaireItem }): void {
    if (event.key === 'edit') this.edit.emit(event.item);
    if (event.key === 'delete') this.delete.emit(event.item);
  }
}
