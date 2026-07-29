import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-not-found-page',
  imports: [RouterLink, MessagePipe],
  templateUrl: './not-found-page.html',
})
export class NotFoundPageComponent {}
