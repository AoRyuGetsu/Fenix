import { Component, inject, computed } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MenuFacade, MenuNode } from '../../menu/facades/menu.facade';
import { AuthFacade } from '../../auth/facades/auth.facade';
import { ConfigService } from '../../services/config.service';
import { CatalogService } from '../../services/catalog.service';
import { MessageService, MessageResolver } from '../../services/message.service';

const ICON_MAP: Record<string, string> = {
  settings: 'bi-gear',
  shield: 'bi-shield-check',
  lock: 'bi-lock',
  people: 'bi-people',
  questionnaire: 'bi-question-circle',
  'person-badge': 'bi-person-badge',
};

@Component({
  selector: 'app-sidebar',
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './sidebar.component.html',
})
export class SidebarComponent {
  private menuFacade = inject(MenuFacade);
  private authFacade = inject(AuthFacade);
  private configService = inject(ConfigService);
  private catalogService = inject(CatalogService);
  private msg = inject(MessageService);

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  userRoleName = computed(() => {
    const roles = this.authFacade.vm().roles;
    if (roles.length === 0) return '';
    const catalog = this.catalogService.getCatalog<{ code: string; name: string }>('roles');
    const match = catalog.find(r => r.code === roles[0]);
    return match?.name ?? roles[0];
  });

  vm = computed(() => ({
    logoUrl: this.configService.logoUrl,
    menuTree: this.menuFacade.menuTree(),
    user: this.authFacade.vm().user,
    userRoleName: this.userRoleName(),
    t: this.t,
  }));

  protected logout(): void {
    if (!confirm(this.msg.get('cr_cnf_logout'))) return;
    this.authFacade.logout();
  }

  icon(item: MenuNode): string {
    return ICON_MAP[item.icon ?? ''] ?? 'bi-circle';
  }
}
