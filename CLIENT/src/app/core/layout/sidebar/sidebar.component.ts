import { Component, inject, computed } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MessagePipe } from '../../../shared/pipes/message.pipe';
import { MenuFacade, MenuNode } from '../../menu/facades/menu.facade';
import { AuthFacade } from '../../auth/facades/auth.facade';
import { ConfigService } from '../../services/config.service';
import { CatalogService } from '../../services/catalog.service';

const ICON_MAP: Record<string, string> = {
  settings: 'bi-gear',
  shield: 'bi-shield-check',
  lock: 'bi-lock',
  people: 'bi-people',
  questionnaire: 'bi-question-circle',
};

@Component({
  selector: 'app-sidebar',
  imports: [RouterLink, RouterLinkActive, MessagePipe],
  templateUrl: './sidebar.component.html',
})
export class SidebarComponent {
  private menuFacade = inject(MenuFacade);
  private authFacade = inject(AuthFacade);
  private configService = inject(ConfigService);
  private catalogService = inject(CatalogService);

  menuTree = this.menuFacade.menuTree;
  authVm = this.authFacade.vm;
  logoUrl = this.configService.logoUrl;

  userRoleName = computed(() => {
    const roles = this.authVm().roles;
    if (roles.length === 0) return '';
    const catalog = this.catalogService.getCatalog<{ code: string; name: string }>('roles');
    const match = catalog.find(r => r.code === roles[0]);
    return match?.name ?? roles[0];
  });

  protected logout(): void {
    this.authFacade.logout();
  }

  icon(item: MenuNode): string {
    return ICON_MAP[item.icon ?? ''] ?? 'bi-circle';
  }
}
