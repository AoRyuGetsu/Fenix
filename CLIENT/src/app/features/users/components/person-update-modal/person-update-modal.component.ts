import { Component, input, output, signal, effect } from '@angular/core';
import { PeopleType, PersonData, UpdatePersonPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-person-update-modal',
  imports: [],
  templateUrl: './person-update-modal.component.html',
})
export class PersonUpdateModalComponent {
  peopleTypes = input<PeopleType[]>([]);
  item = input<PersonData | null>(null);
  loading = input(false);

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

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
