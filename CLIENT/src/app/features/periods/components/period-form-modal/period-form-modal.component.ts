import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { PeriodItem, PeriodPayload } from '../../services/periods-http.service';

@Component({
  selector: 'app-period-form-modal',
  standalone: true,
  templateUrl: './period-form-modal.component.html',
})
export class PeriodFormModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  editing = input<PeriodItem | null>(null);
  saving = input(false);

  save = output<PeriodPayload>();
  close = output<void>();

  name = signal('');
  startDate = signal('');
  endDate = signal('');

  vm = computed(() => ({
    editing: this.editing(),
    saving: this.saving(),
    name: this.name(),
    startDate: this.startDate(),
    endDate: this.endDate(),
    t: this.t,
  }));

  constructor() {
    effect(() => {
      const item = this.editing();
      if (item) {
        this.name.set(item.name);
        this.startDate.set(item.start_date);
        this.endDate.set(item.end_date);
      } else {
        this.name.set('');
        this.startDate.set('');
        this.endDate.set('');
      }
    });
  }

  onSubmit(): void {
    if (!this.name() || !this.startDate() || !this.endDate()) return;

    this.save.emit({
      name: this.name(),
      start_date: this.startDate(),
      end_date: this.endDate(),
    });
  }

  onClose(): void {
    this.close.emit();
  }
}
