import { Component, input, output, signal, effect } from '@angular/core';
import { UserData, UpdateAdminPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-admin-update-modal',
  imports: [],
  templateUrl: './admin-update-modal.component.html',
})
export class AdminUpdateModalComponent {
  item = input<UserData | null>(null);
  loading = input(false);

  save = output<UpdateAdminPayload>();
  close = output<void>();

  email = signal('');
  active = signal(true);
  changePassword = signal(false);
  password = signal('');

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
