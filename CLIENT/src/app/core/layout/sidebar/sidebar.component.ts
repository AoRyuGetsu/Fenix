import { Component, inject } from '@angular/core';
import { RouterModule } from '@angular/router';
import { MenuFacade, MenuNode } from '../../menu/facades/menu.facade';
import { AuthFacade } from '../../auth/facades/auth.facade';

const ICON_MAP: Record<string, string> = {
  settings: 'bi-gear',
  shield: 'bi-shield-check',
  lock: 'bi-lock',
  people: 'bi-people',
};

@Component({
  selector: 'app-sidebar',
  imports: [RouterModule],
  templateUrl: './sidebar.component.html',
})
export class SidebarComponent {
  private menuFacade = inject(MenuFacade);
  private authFacade = inject(AuthFacade);

  menuTree = this.menuFacade.menuTree;
  authVm = this.authFacade.vm;

  displayName(): string {
    return this.authFacade.vm().user?.email?.split('@')[0] ?? '';
  }

  icon(item: MenuNode): string {
    return ICON_MAP[item.icon ?? ''] ?? 'bi-circle';
  }
}
