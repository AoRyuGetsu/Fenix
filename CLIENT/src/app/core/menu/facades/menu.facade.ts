import { Injectable, signal, computed } from '@angular/core';
import { MenuItem } from '../../auth/services/auth-http.service';

export interface MenuNode extends MenuItem {
  children: MenuNode[];
}

const STORAGE_MENUS = 'user_menus';

@Injectable({ providedIn: 'root' })
export class MenuFacade {
  private flatMenus = signal<MenuItem[]>([]);

  menuTree = computed<MenuNode[]>(() => {
    const flat = this.flatMenus();
    const map = new Map<number, MenuNode>();
    const roots: MenuNode[] = [];

    for (const item of flat) {
      map.set(item.id, { ...item, children: [] });
    }

    for (const item of flat) {
      const node = map.get(item.id)!;
      if (item.parent_id !== null && map.has(item.parent_id)) {
        map.get(item.parent_id)!.children.push(node);
      } else if (item.parent_id === null) {
        roots.push(node);
      }
    }

    const sortNodes = (nodes: MenuNode[]) => {
      nodes.sort((a, b) => a.menu_order - b.menu_order);
      nodes.forEach(n => sortNodes(n.children));
    };
    sortNodes(roots);

    return roots;
  });

  setMenus(menus: MenuItem[]): void {
    localStorage.setItem(STORAGE_MENUS, JSON.stringify(menus));
    this.flatMenus.set(menus);
  }

  clear(): void {
    localStorage.removeItem(STORAGE_MENUS);
    this.flatMenus.set([]);
  }

  loadFromStorage(): void {
    const stored = localStorage.getItem(STORAGE_MENUS);
    if (stored) {
      try {
        this.flatMenus.set(JSON.parse(stored));
      } catch {
        this.flatMenus.set([]);
      }
    }
  }
}
