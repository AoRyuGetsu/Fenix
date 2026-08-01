import { Component, input, output, signal, computed } from '@angular/core';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';
import { PeopleType, Parentesco, CreatePersonPayload } from '../../services/users-http.service';
import { AutocompleteComponent } from '../../../../shared/components/autocomplete/autocomplete.component';
import { TeamItem } from '../../../teams/services/teams-http.service';

@Component({
  selector: 'app-person-create-modal',
  imports: [AutocompleteComponent, MessagePipe],
  templateUrl: './person-create-modal.component.html',
})
export class PersonCreateModalComponent {
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

  isJug = computed(() => this.type() === 'JUG');

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
    if (!this.firstName() || !this.firstSurname() || !this.documentNumber() || !this.email() || !this.type()) {
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
