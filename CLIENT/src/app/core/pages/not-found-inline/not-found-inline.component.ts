import { Component } from '@angular/core';
import { MessagePipe } from '../../../shared/pipes/message.pipe';

@Component({
  standalone: true,
  imports: [MessagePipe],
  templateUrl: './not-found-inline.html',
})
export class NotFoundInlineComponent {}
