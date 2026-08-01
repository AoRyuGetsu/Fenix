import { Component, input, output, computed } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { PersonData, UserData, PlayerSpecData } from '../../services/users-http.service';

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
  playerSpecs = input<Record<number, PlayerSpecData>>({});

  pageChange = output<number>();
  delete = output<number>();
  update = output<TableItem>();
  restore = output<number>();
  spec = output<number>();

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
          { label: 'Fecha de nacimiento', key: '_specBirthDate' },
          { label: 'Estatura', key: '_specHeight' },
          { label: 'Peso', key: '_specWeight' },
          { label: 'Género', key: '_specGender' },
          { label: 'Nacionalidad', key: '_specNationality' },
          { label: 'Tipo de sangre', key: '_specBlood' },
          { label: 'Posición', key: '_specPosition' },
          { label: 'Dorsal', key: '_specJersey' },
          { label: 'Pie dominante', key: '_specFoot' },
          { label: 'Fecha de ingreso', key: '_specEntryDate' },
          { label: 'Estado', key: '_specStatus' },
          { label: 'Contacto emergencia', key: '_specEmerName' },
          { label: 'Teléfono emergencia', key: '_specEmerPhone' },
          { label: 'Parentesco emergencia', key: '_specEmerRel' },
          { label: 'Condiciones médicas', key: '_specMedCond' },
          { label: 'Alergias', key: '_specAllergies' },
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
    if (this.type() === 'person') {
      base.unshift({
        key: 'spec',
        icon: 'bi-person-badge',
        label: 'Ficha',
        btnClass: 'btn-outline-primary',
        visible: (item: any) => item.type === 'JUG',
      });
    }
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
    const ps = this.playerSpecs();
    return this.items().map(item => {
      if (t === 'person') {
        const p = item as PersonData;
        const spec = p.user_id ? ps[p.user_id] : null;
        return {
          ...p,
          _displayName: `${p.first_name} ${p.middle_name ?? ''}`.trim(),
          _displaySurnames: `${p.first_surname} ${p.second_surname ?? ''}`.trim(),
          _displayFullName: [p.first_name, p.middle_name, p.first_surname, p.second_surname].filter(Boolean).join(' '),
          _associatedUser: p.user_id ? `Sí (ID: ${p.user_id})` : 'No',
          _specBirthDate: spec?.birth_date ?? null,
          _specHeight: spec?.height_cm ? `${spec.height_cm} cm` : null,
          _specWeight: spec?.weight_kg ? `${spec.weight_kg} kg` : null,
          _specGender: spec?.gender ?? null,
          _specNationality: spec?.nationality ?? null,
          _specBlood: spec?.blood_type ?? null,
          _specPosition: spec?.position ?? null,
          _specJersey: spec?.jersey_number_1 ?? null,
          _specFoot: spec?.dominant_foot ?? null,
          _specEntryDate: spec?.entry_date ?? null,
          _specStatus: spec?.status ?? null,
          _specEmerName: spec?.emergency_contact_name ?? null,
          _specEmerPhone: spec?.emergency_contact_phone ?? null,
          _specEmerRel: spec?.emergency_contact_relationship ?? null,
          _specMedCond: spec?.medical_conditions ?? null,
          _specAllergies: spec?.allergies ?? null,
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
    } else if (event.key === 'spec') {
      this.spec.emit(event.item.id);
    }
  }
}
