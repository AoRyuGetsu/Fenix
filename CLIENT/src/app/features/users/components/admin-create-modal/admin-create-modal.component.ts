import { Component, computed, inject, input, output, signal } from '@angular/core';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { CreateAdminPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-admin-create-modal',
  templateUrl: './admin-create-modal.component.html',
})
export class AdminCreateModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  loading = input(false);

  save = output<CreateAdminPayload>();
  close = output<void>();

  email = signal('');
  password = signal('');

  vm = computed(() => ({
    loading: this.loading(),
    email: this.email(),
    password: this.password(),
    t: this.t,
  }));

  onSubmit(): void {
    if (!this.email() || !this.password()) return;

    this.save.emit({
      email: this.email(),
      password: this.password(),
    });
  }

  onClose(): void {
    this.close.emit();
  }
}
