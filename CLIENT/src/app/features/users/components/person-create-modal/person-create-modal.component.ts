import { Component, input, output, signal } from '@angular/core';
import { PeopleType, CreatePersonPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-person-create-modal',
  imports: [],
  templateUrl: './person-create-modal.component.html',
})
export class PersonCreateModalComponent {
  peopleTypes = input<PeopleType[]>([]);
  loading = input(false);

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

  onSubmit(): void {
    if (!this.firstName() || !this.firstSurname() || !this.documentNumber() || !this.email() || !this.type()) {
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

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
