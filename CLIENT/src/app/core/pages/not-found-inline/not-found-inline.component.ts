import { Component, computed, inject } from '@angular/core';
import { MessageService, MessageResolver } from '../../../core/services/message.service';

@Component({
  standalone: true,
  templateUrl: './not-found-inline.html',
})
export class NotFoundInlineComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ t: this.t }));
}
