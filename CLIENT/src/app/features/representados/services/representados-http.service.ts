import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';

export interface RepresentadoPerson {
  first_name: string;
  middle_name: string | null;
  first_surname: string;
  second_surname: string | null;
  document_number: string;
  email: string | null;
  phone: string | null;
  address: string | null;
}

export interface RepresentadoSpec {
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
  status: string;
}

export interface RepresentadoItem {
  id: number;
  email: string;
  display_name: string;
  person: RepresentadoPerson | null;
  parentesco: { id: number; code: string; description: string };
  player_spec: RepresentadoSpec | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  perPage: number;
}

@Injectable({ providedIn: 'root' })
export class RepresentadosHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getMyPlayers(page: number, perPage: number, search?: string): Observable<PaginatedResponse<RepresentadoItem>> {
    const params: Record<string, string> = { page: String(page), perPage: String(perPage) };
    if (search) {
      params['search'] = search;
    }
    return this.http.get<PaginatedResponse<RepresentadoItem>>(
      `${this.configService.apiUrl}/representatives/my/players`,
      { params },
    );
  }
}
