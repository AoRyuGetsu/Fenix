import { Component, input, output, computed } from '@angular/core';
import { UserItem, PlayerSpecData } from '../../services/evaluations-http.service';

@Component({
  selector: 'app-player-info-modal',
  templateUrl: './player-info-modal.component.html',
})
export class PlayerInfoModalComponent {
  user = input.required<UserItem>();
  spec = input<PlayerSpecData | null>(null);
  loading = input(false);

  close = output<void>();

  personName = computed(() => {
    const p = this.user().person;
    if (!p) return this.user().display_name || this.user().email;
    return [p.first_name, p.middle_name, p.first_surname, p.second_surname].filter(Boolean).join(' ');
  });

  rep = computed(() => this.user().representative);

  onClose(): void {
    this.close.emit();
  }
}
