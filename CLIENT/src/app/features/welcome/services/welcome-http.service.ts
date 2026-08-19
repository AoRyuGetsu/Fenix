import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ConfigService } from '../../../core/services/config.service';

export interface DashboardStats {
  questionnaires_total: number;
  users_by_role: Record<string, number>;
  my_evaluations_total: number;
  evaluations_by_period: { period_id: number; period_name: string; total: number }[];
}

export interface PlayerUserDetail {
  id: number;
  email: string;
  roles: string[];
  team: { id: number; name: string } | null;
  representative: {
    id: number;
    display_name: string;
    parentesco: { id: number; code: string; description: string };
  } | null;
  person: {
    id: number;
    type: string;
    first_name: string;
    middle_name: string | null;
    first_surname: string;
    second_surname: string | null;
    document_number: string;
    email: string | null;
    phone: string | null;
    address: string | null;
  } | null;
}

export interface PlayerSpecDetail {
  user_id: number;
  user_email: string;
  birth_date: string;
  height_cm: number;
  weight_kg: number;
  gender: string;
  entry_date: string;
  medical_conditions: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  emergency_contact_relationship: string | null;
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
  profile_photo_url: string | null;
  status: string | null;
}

@Injectable({ providedIn: 'root' })
export class WelcomeHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getStats() {
    return this.http.get<DashboardStats>(`${this.configService.apiUrl}/dashboard`);
  }

  getUser(userId: number) {
    return this.http.get<PlayerUserDetail>(`${this.configService.apiUrl}/users/${userId}`);
  }

  getPlayerSpec(userId: number) {
    return this.http.get<PlayerSpecDetail>(`${this.configService.apiUrl}/player-specifications/${userId}`);
  }
}
