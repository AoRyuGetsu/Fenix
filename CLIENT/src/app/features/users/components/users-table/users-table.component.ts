import { Component, input, output, computed } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { PersonData, UserData } from '../../services/users-http.service';

type TableItem = PersonData | UserData;

@Component({
  selector: 'app-users-table',
  imports: [GTableComponent],
  templateUrl: './users-table.component.html',
})
export class UsersTableComponent {
  items = input<TableItem[]>([]);
  type = input<'person' | 'admin'>('person');
  total = input(0);
  page = input(1);
  perPage = input(20);

  pageChange = output<number>();
  delete = output<number>();
  update = output<TableItem>();
  restore = output<number>();

  columns = computed<GtColumn<any>[]>(() => {
    if (this.type() === 'person') {
      return [
        { key: '_displayName', label: 'Nombres' },
        { key: '_displaySurnames', label: 'Apellidos' },
        { key: 'document_number', label: 'Cédula' },
        { key: 'email', label: 'Correo' },
        { key: 'phone', label: 'Teléfono' },
      ];
    }
    return [
      { key: 'email', label: 'Correo' },
      { key: '_rolesDisplay', label: 'Roles' },
      { key: 'active', label: 'Activo', format: 'boolean' },
      {
        key: '_deletedStatus',
        label: 'Estado',
        format: 'badge',
        badgeMap: {
          active: { class: 'bg-success', label: 'Activo' },
          deleted: { class: 'bg-danger', label: 'Eliminado' },
        },
      },
    ];
  });

  detailConfig = computed<GtDetailConfig | null>(() => {
    if (this.type() === 'person') {
      return {
        fields: [
          { label: 'Nombre completo', key: '_displayFullName' },
          { label: 'Cédula', key: 'document_number' },
          { label: 'Correo', key: 'email' },
          { label: 'Teléfono', key: 'phone' },
          { label: 'Dirección', key: 'address' },
          { label: 'Tipo', key: 'type' },
          { label: 'Usuario asociado', key: '_associatedUser' },
          { label: 'Creado', key: 'created_at', format: 'date' },
          { label: 'Actualizado', key: 'updated_at', format: 'date' },
        ],
      };
    }
    return {
      fields: [
        { label: 'Correo', key: 'email' },
        { label: 'Roles', key: '_rolesDisplay' },
        { label: 'Activo', key: 'active', format: 'boolean' },
        { label: 'Estado', key: '_deletedStatus', format: 'badge', badgeMap: { active: { class: 'bg-success', label: 'Activo' }, deleted: { class: 'bg-danger', label: 'Eliminado' } } },
        { label: 'Creado', key: 'created_at', format: 'date' },
        { label: 'Actualizado', key: 'updated_at', format: 'date' },
      ],
    };
  });

  actions = computed<GtAction<any>[]>(() => {
    const base: GtAction<any>[] = [
      { key: 'edit', icon: 'bi-pencil', label: 'Actualizar', btnClass: 'btn-outline-info' },
      { key: 'delete', icon: 'bi-trash', label: 'Eliminar', btnClass: 'btn-outline-danger' },
    ];
    if (this.type() === 'admin') {
      base.push({
        key: 'restore',
        icon: 'bi-arrow-counterclockwise',
        label: 'Restaurar',
        btnClass: 'btn-outline-warning',
        visible: (item: any) => !!item.deleted_at,
      });
    }
    return base;
  });

  processedItems = computed<any[]>(() => {
    const t = this.type();
    return this.items().map(item => {
      if (t === 'person') {
        const p = item as PersonData;
        return {
          ...p,
          _displayName: `${p.first_name} ${p.middle_name ?? ''}`.trim(),
          _displaySurnames: `${p.first_surname} ${p.second_surname ?? ''}`.trim(),
          _displayFullName: [p.first_name, p.middle_name, p.first_surname, p.second_surname].filter(Boolean).join(' '),
          _associatedUser: p.user_id ? `Sí (ID: ${p.user_id})` : 'No',
        };
      }
      const u = item as UserData;
      return {
        ...u,
        _rolesDisplay: u.roles?.join(', ') ?? '',
        _deletedStatus: u.deleted_at ? 'deleted' : 'active',
      };
    });
  });

  onAction(event: { key: string; item: any }): void {
    if (event.key === 'edit') {
      this.update.emit(event.item);
    } else if (event.key === 'delete') {
      this.delete.emit(event.item.id);
    } else if (event.key === 'restore') {
      this.restore.emit(event.item.id);
    }
  }
}
