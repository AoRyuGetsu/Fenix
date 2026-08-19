import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { RoleItem } from '../../../../core/auth/services/auth-http.service';
import { QuestionnaireItem } from '../../services/questionnaire-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-questionnaire-update-modal',
  standalone: true,
  templateUrl: './questionnaire-update-modal.component.html',
})
export class QuestionnaireUpdateModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  visible = input(false);
  loading = input(false);
  item = input<QuestionnaireItem | null>(null);
  roles = input<RoleItem[]>([]);
  close = output<void>();
  save = output<{ title: string; description?: string; active: boolean; responder_role_id: number; target_role_id: number }>();

  title = signal('');
  description = signal('');
  active = signal(true);
  responderRoleId = signal(0);
  targetRoleId = signal(0);

  vm = computed(() => ({
    visible: this.visible(),
    loading: this.loading(),
    roles: this.roles(),
    title: this.title(),
    description: this.description(),
    active: this.active(),
    responderRoleId: this.responderRoleId(),
    targetRoleId: this.targetRoleId(),
    t: this.t,
  }));

  constructor() {
    effect(() => {
      const value = this.item();
      if (value) {
        this.title.set(value.title);
        this.description.set(value.description ?? '');
        this.active.set(value.active);
        this.responderRoleId.set(value.responder_role_id);
        this.targetRoleId.set(value.target_role_id);
      }
    });
  }

  onSave(): void {
    this.save.emit({
      title: this.title(),
      description: this.description() || undefined,
      active: this.active(),
      responder_role_id: this.responderRoleId(),
      target_role_id: this.targetRoleId(),
    });
  }
}
