import { Component, input, output, computed, inject, signal, TemplateRef, ViewChild } from '@angular/core';
import { GTableComponent } from '../../../../shared/components/gtable/gtable.component';
import { GtColumn, GtAction, GtDetailConfig } from '../../../../shared/components/gtable/gtable.types';
import { PersonData, UserData, PlayerSpecData, PlayerRepData } from '../../services/users-http.service';
import { PlayerDetailCardsComponent } from '../../../../shared/components/player-detail/player-detail-cards.component';
import { buildPlayerDetail } from '../../../../shared/components/player-detail/player-detail.util';
import { PlayerDetailModel } from '../../../../shared/components/player-detail/player-detail.types';
import { PersonDetailCardsComponent } from '../../../../shared/components/person-detail/person-detail-cards.component';
import { PersonDetailModel } from '../../../../shared/components/person-detail/person-detail.types';
import { MessageService } from '../../../../core/services/message.service';

type TableItem = PersonData | UserData;

@Component({
  selector: 'app-users-table',
  imports: [GTableComponent, PlayerDetailCardsComponent, PersonDetailCardsComponent],
  templateUrl: './users-table.component.html',
})
export class UsersTableComponent {
  private msg = inject(MessageService);

  items = input<TableItem[]>([]);
  type = input<'person' | 'admin'>('person');
  personType = input<string | null>(null);
  total = input(0);
  page = input(1);
  perPage = input(20);
  playerSpecs = input<Record<number, PlayerSpecData>>({});
  playerReps = input<Record<number, PlayerRepData>>({});

  pageChange = output<number>();
  delete = output<number>();
  update = output<TableItem>();
  restore = output<number>();
  spec = output<number>();

  @ViewChild('playerDetailTpl') set playerDetailTpl(v: TemplateRef<{ $implicit: any }>) {
    this.playerDetailTplSignal.set(v);
  }

  private playerDetailTplSignal = signal<TemplateRef<{ $implicit: any }> | null>(null);

  @ViewChild('personDetailTpl') set personDetailTpl(v: TemplateRef<{ $implicit: any }>) {
    this.personDetailTplSignal.set(v);
  }

  private personDetailTplSignal = signal<TemplateRef<{ $implicit: any }> | null>(null);

  columns = computed<GtColumn<any>[]>(() => {
    if (this.type() === 'person') {
      return [
        { key: '_displayName', label: this.msg.get('usr_lbl_names') },
        { key: '_displaySurnames', label: this.msg.get('usr_lbl_surnames') },
        { key: 'document_number', label: this.msg.get('usr_lbl_document_number') },
        { key: 'email', label: this.msg.get('usr_lbl_email_short') },
        { key: 'phone', label: this.msg.get('usr_lbl_phone') },
      ];
    }
    return [
      { key: 'email', label: this.msg.get('usr_lbl_email_short') },
      { key: '_rolesDisplay', label: this.msg.get('usr_lbl_roles') },
      { key: 'active', label: this.msg.get('usr_lbl_active'), format: 'boolean' },
      {
        key: '_deletedStatus',
        label: this.msg.get('usr_lbl_status'),
        format: 'badge',
        badgeMap: {
          active: { class: 'bg-success', label: this.msg.get('usr_lbl_active') },
          deleted: { class: 'bg-danger', label: this.msg.get('usr_lbl_deleted') },
        },
      },
    ];
  });

  detailConfig = computed<GtDetailConfig | null>(() => {
    if (this.type() === 'person') {
      if (this.personType() === 'JUG') {
        const tpl = this.playerDetailTplSignal();
        if (tpl) return { template: tpl };
      } else {
        const tpl = this.personDetailTplSignal();
        if (tpl) return { template: tpl };
      }

      return {
        fields: [
          { label: this.msg.get('usr_lbl_full_name'), key: '_displayFullName' },
          { label: this.msg.get('usr_lbl_document_number'), key: 'document_number' },
          { label: this.msg.get('usr_lbl_email_short'), key: 'email' },
          { label: this.msg.get('usr_lbl_phone'), key: 'phone' },
          { label: this.msg.get('usr_lbl_address'), key: 'address' },
          { label: this.msg.get('usr_lbl_type'), key: 'type' },
          { label: this.msg.get('usr_lbl_associated_user'), key: '_associatedUser' },
          { label: this.msg.get('usr_lbl_created'), key: 'created_at', format: 'date' },
          { label: this.msg.get('usr_lbl_updated'), key: 'updated_at', format: 'date' },
        ],
      };
    }
    return {
      fields: [
        { label: this.msg.get('usr_lbl_email_short'), key: 'email' },
        { label: this.msg.get('usr_lbl_roles'), key: '_rolesDisplay' },
        { label: this.msg.get('usr_lbl_active'), key: 'active', format: 'boolean' },
        { label: this.msg.get('usr_lbl_status'), key: '_deletedStatus', format: 'badge', badgeMap: { active: { class: 'bg-success', label: this.msg.get('usr_lbl_active') }, deleted: { class: 'bg-danger', label: this.msg.get('usr_lbl_deleted') } } },
        { label: this.msg.get('usr_lbl_created'), key: 'created_at', format: 'date' },
        { label: this.msg.get('usr_lbl_updated'), key: 'updated_at', format: 'date' },
      ],
    };
  });

  actions = computed<GtAction<any>[]>(() => {
    const base: GtAction<any>[] = [
      { key: 'edit', icon: 'bi-pencil', label: this.msg.get('cr_btn_update'), btnClass: 'btn-outline-info' },
      { key: 'delete', icon: 'bi-trash', label: this.msg.get('cr_btn_delete'), btnClass: 'btn-outline-danger' },
    ];
    if (this.type() === 'person') {
      base.unshift({
        key: 'spec',
        icon: 'bi-person-badge',
        label: this.msg.get('usr_lbl_ficha'),
        btnClass: 'btn-outline-primary',
        visible: (item: any) => item.type === 'JUG',
      });
    }
    if (this.type() === 'admin') {
      base.push({
        key: 'restore',
        icon: 'bi-arrow-counterclockwise',
        label: this.msg.get('cr_btn_restore'),
        btnClass: 'btn-outline-warning',
        visible: (item: any) => !!item.deleted_at,
      });
    }
    return base;
  });

  processedItems = computed<any[]>(() => {
    const t = this.type();
    const ps = this.playerSpecs();
    const reps = this.playerReps();
    return this.items().map(item => {
      if (t === 'person') {
        const p = item as PersonData;
        const userId = p.user_id;
        const spec = userId ? ps[userId] : null;
        const rep = userId ? reps[userId] : null;
        const fullName = [p.first_name, p.middle_name, p.first_surname, p.second_surname].filter(Boolean).join(' ');
        const associatedUser = p.user_id ? this.msg.get('usr_lbl_user_yes_id', { id: p.user_id }) : this.msg.get('usr_lbl_associated_user_no');
        const playerModel: PlayerDetailModel = buildPlayerDetail(
          p.user_id ?? p.id,
          fullName,
          p.email,
          p,
          spec ?? null,
          {
            representative: rep
              ? { id: rep.id, display_name: rep.display_name, email: rep.email }
              : null,
            parentesco: rep?.parentesco?.description ?? null,
          },
        );
        const personModel: PersonDetailModel = {
          display_name: fullName,
          document_number: p.document_number,
          email: p.email,
          phone: p.phone,
          address: p.address,
          type: p.type,
          associated_user: associatedUser,
          created_at: p.created_at,
          updated_at: p.updated_at,
        };
        return {
          ...p,
          _displayName: `${p.first_name} ${p.middle_name ?? ''}`.trim(),
          _displaySurnames: `${p.first_surname} ${p.second_surname ?? ''}`.trim(),
          _displayFullName: fullName,
          _playerModel: playerModel,
          _personModel: personModel,
          _associatedUser: associatedUser,
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
