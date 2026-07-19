import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../services/config.service';

export interface PermissionItem {
  id: number;
  name: string;
}

export interface RoleWithPermissions {
  id: number;
  name: string;
  permissions: string[];
}

export interface RolesPermissionsResponse {
  permissions: PermissionItem[];
  roles: RoleWithPermissions[];
}

@Injectable({ providedIn: 'root' })
export class RolesPermisosHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getRolesPermissions(): Observable<RolesPermissionsResponse> {
    return this.http.get<RolesPermissionsResponse>(`${this.configService.apiUrl}/roles/permissions`);
  }
}
