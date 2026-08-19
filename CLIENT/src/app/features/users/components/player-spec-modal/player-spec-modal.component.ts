import { Component, computed, inject, input, output, signal, OnInit } from '@angular/core';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';
import { PlayerSpecData, CreatePlayerSpecPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-player-spec-modal',
  templateUrl: './player-spec-modal.component.html',
})
export class PlayerSpecModalComponent implements OnInit {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

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
  jerseyNumber1 = signal<number | null>(null);
  jerseyNumber2 = signal<number | null>(null);
  jerseyNumber3 = signal<number | null>(null);
  dominantFoot = signal('');
  nationality = signal('');
  bloodType = signal('');
  allergies = signal('');
  insurance = signal('');
  medications = signal('');
  basketballExperience = signal('');
  previousClub = signal('');
  category = signal('');
  shirtSize = signal('');
  profilePhotoUrl = signal('');
  status = signal('active');

  isUpdate = computed(() => this.existingData() !== null);

  vm = computed(() => ({
    loading: this.loading(),
    isUpdate: this.isUpdate(),
    birthDate: this.birthDate(),
    heightCm: this.heightCm(),
    weightKg: this.weightKg(),
    gender: this.gender(),
    entryDate: this.entryDate(),
    medicalConditions: this.medicalConditions(),
    emergencyContactName: this.emergencyContactName(),
    emergencyContactPhone: this.emergencyContactPhone(),
    emergencyContactRelationship: this.emergencyContactRelationship(),
    position: this.position(),
    jerseyNumber1: this.jerseyNumber1(),
    jerseyNumber2: this.jerseyNumber2(),
    jerseyNumber3: this.jerseyNumber3(),
    dominantFoot: this.dominantFoot(),
    nationality: this.nationality(),
    bloodType: this.bloodType(),
    allergies: this.allergies(),
    insurance: this.insurance(),
    medications: this.medications(),
    basketballExperience: this.basketballExperience(),
    previousClub: this.previousClub(),
    category: this.category(),
    shirtSize: this.shirtSize(),
    profilePhotoUrl: this.profilePhotoUrl(),
    status: this.status(),
    t: this.t,
  }));

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
      this.jerseyNumber1.set(data.jersey_number_1);
      this.jerseyNumber2.set(data.jersey_number_2);
      this.jerseyNumber3.set(data.jersey_number_3);
      this.dominantFoot.set(data.dominant_foot ?? '');
      this.nationality.set(data.nationality ?? '');
      this.bloodType.set(data.blood_type ?? '');
      this.allergies.set(data.allergies ?? '');
      this.insurance.set(data.insurance ?? '');
      this.medications.set(data.medications ?? '');
      this.basketballExperience.set(data.basketball_experience === null ? '' : data.basketball_experience ? '1' : '0');
      this.previousClub.set(data.previous_club ?? '');
      this.category.set(data.category ?? '');
      this.shirtSize.set(data.shirt_size ?? '');
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
      jersey_number_1: this.jerseyNumber1(),
      jersey_number_2: this.jerseyNumber2(),
      jersey_number_3: this.jerseyNumber3(),
      dominant_foot: this.dominantFoot() || null,
      nationality: this.nationality() || null,
      blood_type: this.bloodType() || null,
      allergies: this.allergies() || null,
      insurance: this.insurance() || null,
      medications: this.medications() || null,
      basketball_experience: this.basketballExperience() === '' ? null : this.basketballExperience() === '1',
      previous_club: this.previousClub() || null,
      category: this.category() || null,
      shirt_size: this.shirtSize() || null,
      profile_photo_url: this.profilePhotoUrl() || null,
      status: this.status(),
    };

    this.save.emit(payload);
  }

  onClose(): void {
    this.close.emit();
  }
}
