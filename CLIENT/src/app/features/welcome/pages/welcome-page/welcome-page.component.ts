import { Component, OnInit, inject } from '@angular/core';
import { AuthFacade } from '../../../../core/auth/facades/auth.facade';
import { WelcomeFacade } from '../../facades/welcome.facade';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-welcome-page',
  standalone: true,
  imports: [MessagePipe],
  templateUrl: './welcome-page.html',
})
export class WelcomePageComponent implements OnInit {
  auth = inject(AuthFacade);
  facade = inject(WelcomeFacade);

  ngOnInit(): void {
    this.facade.load();
  }

  getTotalPages(total: number, perPage: number): number {
    return Math.max(1, Math.ceil(total / perPage));
  }
}
