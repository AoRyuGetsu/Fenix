import { Component, input, output, signal, computed, OnInit, effect } from '@angular/core';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';
import { PlayerSpecData, CreatePlayerSpecPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-player-spec-modal',
  imports: [MessagePipe],
  templateUrl: './player-spec-modal.component.html',
})
export class PlayerSpecModalComponent implements OnInit {
  userId = input.required<number>();
  existingData = input<PlayerSpecData | null>(null);
  loading = input(false);

  save = output<CreatePlayerSpecPayload>();
  close = output<void>();

  birthDate = signal('');
  heightCm = signal<number | null>(null);
  weightKg = signal<number | null>(null);
  gender = signal('');
  entryDate = signal('');
  medicalConditions = signal('');
  emergencyContactName = signal('');
  emergencyContactPhone = signal('');
  emergencyContactRelationship = signal('');
  position = signal('');
  jerseyNumber = signal<number | null>(null);
  dominantFoot = signal('');
  nationality = signal('');
  bloodType = signal('');
  allergies = signal('');
  profilePhotoUrl = signal('');
  status = signal('active');

  isUpdate = computed(() => this.existingData() !== null);

  ngOnInit(): void {
    const data = this.existingData();
    if (data) {
      this.birthDate.set(data.birth_date);
      this.heightCm.set(data.height_cm);
      this.weightKg.set(data.weight_kg);
      this.gender.set(data.gender);
      this.entryDate.set(data.entry_date);
      this.medicalConditions.set(data.medical_conditions ?? '');
      this.emergencyContactName.set(data.emergency_contact_name);
      this.emergencyContactPhone.set(data.emergency_contact_phone);
      this.emergencyContactRelationship.set(data.emergency_contact_relationship);
      this.position.set(data.position ?? '');
      this.jerseyNumber.set(data.jersey_number);
      this.dominantFoot.set(data.dominant_foot ?? '');
      this.nationality.set(data.nationality ?? '');
      this.bloodType.set(data.blood_type ?? '');
      this.allergies.set(data.allergies ?? '');
      this.profilePhotoUrl.set(data.profile_photo_url ?? '');
      this.status.set(data.status);
    }
  }

  onSubmit(): void {
    if (!this.birthDate() || !this.heightCm() || !this.weightKg() || !this.gender() || !this.entryDate() || !this.emergencyContactName() || !this.emergencyContactPhone() || !this.emergencyContactRelationship()) {
      return;
    }

    const payload: CreatePlayerSpecPayload = {
      user_id: this.userId(),
      birth_date: this.birthDate(),
      height_cm: this.heightCm()!,
      weight_kg: this.weightKg()!,
      gender: this.gender(),
      entry_date: this.entryDate(),
      medical_conditions: this.medicalConditions() || null,
      emergency_contact_name: this.emergencyContactName(),
      emergency_contact_phone: this.emergencyContactPhone(),
      emergency_contact_relationship: this.emergencyContactRelationship(),
      position: this.position() || null,
      jersey_number: this.jerseyNumber(),
      dominant_foot: this.dominantFoot() || null,
      nationality: this.nationality() || null,
      blood_type: this.bloodType() || null,
      allergies: this.allergies() || null,
      profile_photo_url: this.profilePhotoUrl() || null,
      status: this.status(),
    };

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
