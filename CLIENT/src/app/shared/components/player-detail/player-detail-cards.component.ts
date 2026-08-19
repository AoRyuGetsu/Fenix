import { Component, computed, inject, input } from '@angular/core';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { PlayerDetailModel } from './player-detail.types';

@Component({
  selector: 'app-player-detail-cards',
  standalone: true,
  templateUrl: './player-detail-cards.component.html',
})
export class PlayerDetailCardsComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  model = input.required<PlayerDetailModel>();
  showRepresentative = input(true);

  private hasSpec = computed(() => this.model().spec !== null);

  private fullName = computed(() => {
    const p = this.model().person;
    if (!p?.first_name) return this.model().display_name;
    return [p.first_name, p.middle_name, p.first_surname, p.second_surname].filter(Boolean).join(' ');
  });

  private jerseyNumbers = computed(() => {
    const s = this.model().spec;
    if (!s) return '';
    return [s.jersey_number_1, s.jersey_number_2, s.jersey_number_3]
      .filter((v): v is number => v !== null && v !== undefined)
      .join(' / ');
  });

  private genderLabel = computed(() => {
    const g = this.model().spec?.gender;
    if (!g) return '';
    if (g === 'male') return this.t('play_spec_lbl_male');
    if (g === 'female') return this.t('play_spec_lbl_female');
    if (g === 'other') return this.t('play_spec_lbl_other');
    return g;
  });

  private footLabel = computed(() => {
    const f = this.model().spec?.dominant_foot;
    if (!f) return '';
    const map: Record<string, string> = {
      left: 'play_spec_lbl_foot_left',
      right: 'play_spec_lbl_foot_right',
      ambidextrous: 'play_spec_lbl_foot_ambidextrous',
    };
    return map[f] ? this.t(map[f]) : f;
  });

  private basketballLabel = computed(() => {
    const v = this.model().spec?.basketball_experience;
    if (v === null || v === undefined) return '';
    return this.t(v ? 'play_spec_lbl_yes' : 'play_spec_lbl_no');
  });

  private statusInfo = computed(() => {
    const st = this.model().spec?.status;
    if (!st) return null;
    const map: Record<string, { key: string; className: string }> = {
      active: { key: 'play_spec_lbl_status_active', className: 'bg-success' },
      injured: { key: 'play_spec_lbl_status_injured', className: 'bg-warning text-dark' },
      suspended: { key: 'play_spec_lbl_status_suspended', className: 'bg-danger' },
      inactive: { key: 'play_spec_lbl_status_inactive', className: 'bg-secondary' },
    };
    const info = map[st];
    return info
      ? { label: this.t(info.key), className: info.className }
      : { label: st, className: 'bg-secondary' };
  });

  vm = computed(() => ({
    model: this.model(),
    showRepresentative: this.showRepresentative(),
    hasSpec: this.hasSpec(),
    fullName: this.fullName(),
    jerseyNumbers: this.jerseyNumbers(),
    genderLabel: this.genderLabel(),
    footLabel: this.footLabel(),
    basketballLabel: this.basketballLabel(),
    statusInfo: this.statusInfo(),
    t: this.t,
  }));
}
