import { Component, inject, OnInit } from '@angular/core';
import { RolesPermisosFacade } from '../../facades/roles-permisos.facade';

@Component({
  selector: 'app-roles-permisos-page',
  imports: [],
  templateUrl: './roles-permisos-page.component.html',
})
export class RolesPermisosPageComponent implements OnInit {
  protected facade = inject(RolesPermisosFacade);
  vm = this.facade.vm;

  ngOnInit(): void {
    this.facade.load();
  }
}
