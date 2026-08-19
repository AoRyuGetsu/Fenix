import { Component, computed, inject, input, output, signal } from '@angular/core';
import { RoleItem } from '../../../../core/auth/services/auth-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-questionnaire-create-modal',
  standalone: true,
  templateUrl: './questionnaire-create-modal.component.html',
})
export class QuestionnaireCreateModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  visible = input(false);
  loading = input(false);
  roles = input<RoleItem[]>([]);
  close = output<void>();
  save = output<{ title: string; description?: string; responder_role_id: number; target_role_id: number }>();

  title = signal('');
  description = signal('');
  responderRoleId = signal(0);
  targetRoleId = signal(0);

  vm = computed(() => ({
    visible: this.visible(),
    loading: this.loading(),
    roles: this.roles(),
    title: this.title(),
    description: this.description(),
    responderRoleId: this.responderRoleId(),
    targetRoleId: this.targetRoleId(),
    t: this.t,
  }));

  onSave(): void {
    this.save.emit({
      title: this.title(),
      description: this.description() || undefined,
      responder_role_id: this.responderRoleId(),
      target_role_id: this.targetRoleId(),
    });
  }
}
