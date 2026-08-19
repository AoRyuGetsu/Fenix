import { Component, computed, inject, input, output, signal } from '@angular/core';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { PeopleType, Parentesco, CreatePersonPayload } from '../../services/users-http.service';
import { AutocompleteComponent } from '../../../../shared/components/autocomplete/autocomplete.component';
import { TeamItem } from '../../../teams/services/teams-http.service';

@Component({
  selector: 'app-person-create-modal',
  imports: [AutocompleteComponent],
  templateUrl: './person-create-modal.component.html',
})
export class PersonCreateModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  peopleTypes = input<PeopleType[]>([]);
  loading = input(false);
  teams = input<TeamItem[]>([]);
  representatives = input<any[]>([]);
  parentescos = input<Parentesco[]>([]);
  loadingTeams = input(false);
  loadingReps = input(false);

  save = output<CreatePersonPayload>();
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
  password = signal('');
  teamId = signal<number | null>(null);
  representativeId = signal<number | null>(null);
  parentescoId = signal<number | null>(null);

  private isJug = computed(() => this.type() === 'JUG');

  private documentValid = computed(() => /^\d{10}$/.test(this.documentNumber()));
  private documentInvalid = computed(() => this.documentNumber() !== '' && !this.documentValid());

  vm = computed(() => ({
    peopleTypes: this.peopleTypes(),
    loading: this.loading(),
    teams: this.teams(),
    representatives: this.representatives(),
    parentescos: this.parentescos(),
    loadingTeams: this.loadingTeams(),
    loadingReps: this.loadingReps(),
    type: this.type(),
    firstName: this.firstName(),
    middleName: this.middleName(),
    firstSurname: this.firstSurname(),
    secondSurname: this.secondSurname(),
    documentNumber: this.documentNumber(),
    email: this.email(),
    phone: this.phone(),
    address: this.address(),
    password: this.password(),
    teamId: this.teamId(),
    representativeId: this.representativeId(),
    parentescoId: this.parentescoId(),
    isJug: this.isJug(),
    documentValid: this.documentValid(),
    documentInvalid: this.documentInvalid(),
    t: this.t,
  }));

  onDocumentInput(event: Event): void {
    this.documentNumber.set((event.target as HTMLInputElement).value.replace(/\D/g, ''));
  }

  onTeamSelect(item: any): void {
    this.teamId.set(item?.id ?? null);
  }

  onRepresentativeSelect(item: any): void {
    this.representativeId.set(item?.id ?? null);
    if (!item) {
      this.parentescoId.set(null);
    }
  }

  onParentescoSelect(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    this.parentescoId.set(value ? Number(value) : null);
  }

  onSubmit(): void {
    if (!this.firstName() || !this.firstSurname() || !this.documentNumber() || !this.email() || !this.type() || !this.documentValid()) {
      return;
    }

    if (this.isJug() && !this.teamId()) {
      return;
    }

    if (this.representativeId() && !this.parentescoId()) {
      return;
    }

    const payload: CreatePersonPayload = {
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

    if (this.password()) {
      payload.password = this.password();
    }

    if (this.isJug()) {
      payload.team_id = this.teamId();
      if (this.representativeId()) {
        payload.representative_id = this.representativeId();
        payload.parentesco_id = this.parentescoId();
      }
    }

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
