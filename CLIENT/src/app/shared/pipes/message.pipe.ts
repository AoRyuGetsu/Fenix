import { Pipe, PipeTransform, inject } from '@angular/core';
import { MessageService } from '../../core/services/message.service';

@Pipe({
  name: 'message',
  standalone: true,
})
export class MessagePipe implements PipeTransform {
  private messages = inject(MessageService);

  transform(key: string, params?: Record<string, string | number>): string {
    return this.messages.get(key, params);
  }
}
