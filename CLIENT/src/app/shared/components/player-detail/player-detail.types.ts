export interface PlayerPerson {
  first_name: string;
  middle_name: string | null;
  first_surname: string;
  second_surname: string | null;
  document_number: string;
  email: string | null;
  phone: string | null;
  address: string | null;
}

export interface PlayerPersonalSpec {
  birth_date: string;
  height_cm: number;
  weight_kg: number;
  gender: string;
  entry_date: string;
  medical_conditions: string | null;
  emergency_contact_name: string;
  emergency_contact_phone: string;
  emergency_contact_relationship: string;
  position: string | null;
  jersey_number_1: number | null;
  jersey_number_2: number | null;
  jersey_number_3: number | null;
  dominant_foot: string | null;
  nationality: string | null;
  blood_type: string | null;
  allergies: string | null;
  insurance: string | null;
  medications: string | null;
  basketball_experience: boolean | null;
  previous_club: string | null;
  category: string | null;
  shirt_size: string | null;
  status: string;
}

export interface PlayerRepresentative {
  id: number;
  display_name: string;
  email?: string | null;
}

export interface PlayerDetailModel {
  user_id: number;
  display_name: string;
  email: string | null;
  person: PlayerPerson | null;
  spec: PlayerPersonalSpec | null;
  representative: PlayerRepresentative | null;
  parentesco: string | null;
  team: string | null;
}