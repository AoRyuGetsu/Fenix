import { Injectable, inject, signal, computed } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { RolesPermisosHttpService, PermissionItem, RoleWithPermissions } from '../services/roles-permisos-http.service';

export interface RolesPermisosState {
  permissions: PermissionItem[];
  roles: RoleWithPermissions[];
  loading: boolean;
  error: string | null;
}

@Injectable({ providedIn: 'root' })
export class RolesPermisosFacade {
  private api = inject(RolesPermisosHttpService);
  private msg = inject(MessageService);

  private state = signal<RolesPermisosState>({
    permissions: [],
    roles: [],
    loading: false,
    error: null,
  });

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.state(), t: this.t }));

  load(): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.getRolesPermissions().subscribe({
      next: res => {
        this.state.update(s => ({
          ...s,
          permissions: res.permissions,
          roles: res.roles,
          loading: false,
          error: null,
        }));
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'rp_err_load', this.msg),
        }));
      },
    });
  }

  roleHasPermission(role: RoleWithPermissions, permissionName: string): boolean {
    return role.permissions.includes(permissionName);
  }
}
