import { Component, inject } from '@angular/core';
import { AuthFacade } from '../../auth/facades/auth.facade';
import { ConfigFacade } from '../../config/facades/config.facade';

@Component({
  selector: 'app-topbar',
  templateUrl: './topbar.component.html',
})
export class TopbarComponent {
  private authFacade = inject(AuthFacade);
  private configFacade = inject(ConfigFacade);
  authVm = this.authFacade.vm;
  configVm = this.configFacade.vm;

  displayName(): string {
    return this.authFacade.vm().user?.email?.split('@')[0] ?? '';
  }

  logout(): void {
    this.authFacade.logout();
  }
}
