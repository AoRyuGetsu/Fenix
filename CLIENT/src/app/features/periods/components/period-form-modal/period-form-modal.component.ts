import { Component, input, output, signal, effect } from '@angular/core';
import { PeriodItem, PeriodPayload } from '../../services/periods-http.service';

@Component({
  selector: 'app-period-form-modal',
  standalone: true,
  imports: [],
  templateUrl: './period-form-modal.component.html',
})
export class PeriodFormModalComponent {
  editing = input<PeriodItem | null>(null);
  saving = input(false);

  save = output<PeriodPayload>();
  close = output<void>();

  name = signal('');
  startDate = signal('');
  endDate = signal('');

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
