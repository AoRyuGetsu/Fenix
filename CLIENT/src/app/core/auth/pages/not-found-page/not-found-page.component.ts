import { Component, computed, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-not-found-page',
  imports: [RouterLink],
  templateUrl: './not-found-page.html',
})
export class NotFoundPageComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ t: this.t }));
}
