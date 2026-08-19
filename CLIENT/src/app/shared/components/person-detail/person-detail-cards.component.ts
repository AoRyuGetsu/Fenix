import { Component, computed, inject, input } from '@angular/core';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { PersonDetailModel } from './person-detail.types';

@Component({
  selector: 'app-person-detail-cards',
  standalone: true,
  templateUrl: './person-detail-cards.component.html',
  imports: [],
})
export class PersonDetailCardsComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  model = input.required<PersonDetailModel>();

  vm = computed(() => ({
    model: this.model(),
    t: this.t,
  }));
}
