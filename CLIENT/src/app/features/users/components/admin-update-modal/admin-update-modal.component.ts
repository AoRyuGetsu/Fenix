import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { UserData, UpdateAdminPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-admin-update-modal',
  templateUrl: './admin-update-modal.component.html',
})
export class AdminUpdateModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  item = input<UserData | null>(null);
  loading = input(false);

  save = output<UpdateAdminPayload>();
  close = output<void>();

  email = signal('');
  active = signal(true);
  changePassword = signal(false);
  password = signal('');

  vm = computed(() => ({
    loading: this.loading(),
    email: this.email(),
    active: this.active(),
    changePassword: this.changePassword(),
    password: this.password(),
    t: this.t,
  }));

  constructor() {
    effect(() => {
      const i = this.item();
      if (i) {
        this.email.set(i.email);
        this.active.set(i.active);
        this.changePassword.set(false);
        this.password.set('');
      }
    });
  }

  onSubmit(): void {
    const payload: UpdateAdminPayload = {
      email: this.email(),
      active: this.active(),
    };

    if (this.changePassword() && this.password()) {
      payload.password = this.password();
    }

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
