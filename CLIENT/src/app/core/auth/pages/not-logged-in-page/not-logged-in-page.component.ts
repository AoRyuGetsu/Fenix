import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-not-logged-in-page',
  imports: [RouterLink, MessagePipe],
  templateUrl: './not-logged-in-page.html',
})
export class NotLoggedInPageComponent {}
