import { Component, input, output, signal, effect, computed } from '@angular/core';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';
import { PeopleType, PersonData, UpdatePersonPayload } from '../../services/users-http.service';
import { AutocompleteComponent } from '../../../../shared/components/autocomplete/autocomplete.component';
import { TeamItem } from '../../../teams/services/teams-http.service';

@Component({
  selector: 'app-person-update-modal',
  imports: [AutocompleteComponent, MessagePipe],
  templateUrl: './person-update-modal.component.html',
})
export class PersonUpdateModalComponent {
  peopleTypes = input<PeopleType[]>([]);
  item = input<PersonData | null>(null);
  loading = input(false);
  teams = input<TeamItem[]>([]);
  representatives = input<any[]>([]);
  loadingTeams = input(false);
  loadingReps = input(false);

  save = output<UpdatePersonPayload>();
  close = output<void>();

  type = signal('');
  firstName = signal('');
  middleName = signal('');
  firstSurname = signal('');
  secondSurname = signal('');
  documentNumber = signal('');
  email = signal('');
  phone = signal('');
  address = signal('');
  changePassword = signal(false);
  password = signal('');
  teamId = signal<number | null>(null);
  representativeId = signal<number | null>(null);

  isJug = computed(() => this.type() === 'JUG');

  constructor() {
    effect(() => {
      const i = this.item();
      if (i) {
        this.type.set(i.type);
        this.firstName.set(i.first_name);
        this.middleName.set(i.middle_name ?? '');
        this.firstSurname.set(i.first_surname);
        this.secondSurname.set(i.second_surname ?? '');
        this.documentNumber.set(i.document_number);
        this.email.set(i.email);
        this.phone.set(i.phone ?? '');
        this.address.set(i.address ?? '');
        this.changePassword.set(false);
        this.password.set('');
      }
    });
  }

  onTeamSelect(item: any): void {
    this.teamId.set(item?.id ?? null);
  }

  onRepresentativeSelect(item: any): void {
    this.representativeId.set(item?.id ?? null);
  }

  getInitialTeam(): any {
    const i = this.item();
    return i && 'team' in i ? (i as any).team : null;
  }

  getInitialRepresentative(): any {
    const i = this.item();
    return i && 'representative' in i ? (i as any).representative : null;
  }

  onSubmit(): void {
    const payload: UpdatePersonPayload = {
      person: {
        type: this.type(),
        first_name: this.firstName(),
        middle_name: this.middleName() || null,
        first_surname: this.firstSurname(),
        second_surname: this.secondSurname() || null,
        document_number: this.documentNumber(),
        email: this.email(),
        phone: this.phone() || null,
        address: this.address() || null,
      },
    };

    if (this.changePassword() && this.password()) {
      payload.password = this.password();
    }

    if (this.isJug()) {
      payload.team_id = this.teamId();
      payload.representative_id = this.representativeId() || null;
    }

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
