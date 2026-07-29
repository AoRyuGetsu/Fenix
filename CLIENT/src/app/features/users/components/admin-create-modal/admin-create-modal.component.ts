import { Component, input, output, signal } from '@angular/core';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';
import { CreateAdminPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-admin-create-modal',
  imports: [MessagePipe],
  templateUrl: './admin-create-modal.component.html',
})
export class AdminCreateModalComponent {
  loading = input(false);

  save = output<CreateAdminPayload>();
  close = output<void>();

  email = signal('');
  password = signal('');

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
