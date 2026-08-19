import {
  PlayerDetailModel,
  PlayerPerson,
  PlayerPersonalSpec,
  PlayerRepresentative,
} from './player-detail.types';

interface PersonSource {
  first_name?: string;
  middle_name?: string | null;
  first_surname?: string;
  second_surname?: string | null;
  document_number?: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
}

interface SpecSource {
  birth_date?: string;
  height_cm?: number;
  weight_kg?: number;
  gender?: string;
  entry_date?: string;
  medical_conditions?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  emergency_contact_relationship?: string | null;
  position?: string | null;
  jersey_number_1?: number | null;
  jersey_number_2?: number | null;
  jersey_number_3?: number | null;
  dominant_foot?: string | null;
  nationality?: string | null;
  blood_type?: string | null;
  allergies?: string | null;
  insurance?: string | null;
  medications?: string | null;
  basketball_experience?: boolean | null;
  previous_club?: string | null;
  category?: string | null;
  shirt_size?: string | null;
  status?: string | null;
}

interface RepSource {
  id?: number;
  display_name?: string;
  email?: string | null;
}

export interface BuildPlayerDetailOptions {
  representative?: RepSource | null;
  parentesco?: string | null;
  team?: string | null;
}

export function buildPlayerDetail(
  userId: number,
  displayName: string,
  email: string | null,
  person: PersonSource | null,
  spec: SpecSource | null,
  options: BuildPlayerDetailOptions = {},
): PlayerDetailModel {
  return {
    user_id: userId,
    display_name: displayName,
    email: email ?? null,
    person: person ? normalizePerson(person) : null,
    spec: spec ? normalizeSpec(spec) : null,
    representative: normalizeRep(options.representative),
    parentesco: options.parentesco ?? null,
    team: options.team ?? null,
  };
}

function normalizePerson(p: PersonSource): PlayerPerson {
  return {
    first_name: p.first_name ?? '',
    middle_name: p.middle_name ?? null,
    first_surname: p.first_surname ?? '',
    second_surname: p.second_surname ?? null,
    document_number: p.document_number ?? '',
    email: p.email ?? null,
    phone: p.phone ?? null,
    address: p.address ?? null,
  };
}

function normalizeSpec(s: SpecSource): PlayerPersonalSpec {
  return {
    birth_date: s.birth_date ?? '',
    height_cm: s.height_cm ?? 0,
    weight_kg: s.weight_kg ?? 0,
    gender: s.gender ?? '',
    entry_date: s.entry_date ?? '',
    medical_conditions: s.medical_conditions ?? null,
    emergency_contact_name: s.emergency_contact_name ?? '',
    emergency_contact_phone: s.emergency_contact_phone ?? '',
    emergency_contact_relationship: s.emergency_contact_relationship ?? '',
    position: s.position ?? null,
    jersey_number_1: s.jersey_number_1 ?? null,
    jersey_number_2: s.jersey_number_2 ?? null,
    jersey_number_3: s.jersey_number_3 ?? null,
    dominant_foot: s.dominant_foot ?? null,
    nationality: s.nationality ?? null,
    blood_type: s.blood_type ?? null,
    allergies: s.allergies ?? null,
    insurance: s.insurance ?? null,
    medications: s.medications ?? null,
    basketball_experience: s.basketball_experience ?? null,
    previous_club: s.previous_club ?? null,
    category: s.category ?? null,
    shirt_size: s.shirt_size ?? null,
    status: s.status ?? '',
  };
}

function normalizeRep(rep: RepSource | null | undefined): PlayerRepresentative | null {
  if (!rep) return null;
  return {
    id: rep.id ?? 0,
    display_name: rep.display_name ?? '',
    email: rep.email ?? null,
  };
}