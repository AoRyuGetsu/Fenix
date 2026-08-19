import { Component, computed, inject, input, output } from '@angular/core';
import { UserItem, PlayerSpecData } from '../../services/evaluations-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-player-info-modal',
  templateUrl: './player-info-modal.component.html',
})
export class PlayerInfoModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  user = input.required<UserItem>();
  spec = input<PlayerSpecData | null>(null);
  loading = input(false);

  close = output<void>();

  private personName = computed(() => {
    const p = this.user().person;
    if (!p) return this.user().display_name || this.user().email;
    return [p.first_name, p.middle_name, p.first_surname, p.second_surname].filter(Boolean).join(' ');
  });

  private rep = computed(() => this.user().representative);

  vm = computed(() => ({
    user: this.user(),
    spec: this.spec(),
    loading: this.loading(),
    personName: this.personName(),
    rep: this.rep(),
    t: this.t,
  }));

  onClose(): void {
    this.close.emit();
  }
}
