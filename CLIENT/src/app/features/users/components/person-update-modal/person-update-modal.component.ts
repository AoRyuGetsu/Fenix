import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { PeopleType, Parentesco, PersonData, UpdatePersonPayload } from '../../services/users-http.service';
import { AutocompleteComponent } from '../../../../shared/components/autocomplete/autocomplete.component';
import { TeamItem } from '../../../teams/services/teams-http.service';

@Component({
  selector: 'app-person-update-modal',
  imports: [AutocompleteComponent],
  templateUrl: './person-update-modal.component.html',
})
export class PersonUpdateModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  peopleTypes = input<PeopleType[]>([]);
  item = input<PersonData | null>(null);
  loading = input(false);
  teams = input<TeamItem[]>([]);
  representatives = input<any[]>([]);
  parentescos = input<Parentesco[]>([]);
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
    changePassword: this.changePassword(),
    password: this.password(),
    teamId: this.teamId(),
    representativeId: this.representativeId(),
    parentescoId: this.parentescoId(),
    isJug: this.isJug(),
    documentValid: this.documentValid(),
    documentInvalid: this.documentInvalid(),
    t: this.t,
  }));

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
        this.representativeId.set(null);
        this.parentescoId.set(null);
        const anyItem = i as any;
        if ('representative' in anyItem && anyItem.representative) {
          this.representativeId.set(anyItem.representative.id ?? null);
          this.parentescoId.set(anyItem.representative.parentesco?.id ?? null);
        }
      }
    });
  }

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
    if (!this.documentValid()) {
      return;
    }
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
      payload.parentesco_id = this.parentescoId() || null;
    }

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
