import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';

export interface PeopleType {
  id: number;
  code: string;
  description: string;
}

export interface Parentesco {
  id: number;
  code: string;
  description: string;
}

export interface PersonData {
  id: number;
  user_id: number | null;
  type: string;
  first_name: string;
  middle_name: string | null;
  first_surname: string;
  second_surname: string | null;
  document_number: string;
  email: string;
  phone: string | null;
  address: string | null;
  create_user: boolean;
  created_at: string;
  updated_at: string;
}

export interface UserPerson {
  id: number;
  type: string;
  first_name: string;
  middle_name: string | null;
  first_surname: string;
  second_surname: string | null;
  document_number: string;
  email: string;
  phone: string | null;
  address: string | null;
}

export interface UserData {
  id: number;
  email: string;
  active: boolean;
  roles: string[];
  person: UserPerson | null;
  team: { id: number; name: string } | null;
  representative: { id: number; display_name: string; parentesco: Parentesco | null } | null;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  perPage: number;
}

export interface CreatePersonPayload {
  password?: string;
  team_id?: number | null;
  representative_id?: number | null;
  parentesco_id?: number | null;
  person: {
    type: string;
    first_name: string;
    middle_name?: string | null;
    first_surname: string;
    second_surname?: string | null;
    document_number: string;
    email: string;
    phone?: string | null;
    address?: string | null;
  };
}

export interface UpdatePersonPayload {
  email?: string;
  password?: string;
  active?: boolean;
  team_id?: number | null;
  representative_id?: number | null;
  parentesco_id?: number | null;
  person?: {
    type?: string;
    first_name?: string;
    middle_name?: string | null;
    first_surname?: string;
    second_surname?: string | null;
    document_number?: string;
    email?: string;
    phone?: string | null;
    address?: string | null;
  };
}

export interface CreateAdminPayload {
  email: string;
  password: string;
}

export interface UpdateAdminPayload {
  email?: string;
  password?: string;
  active?: boolean;
}

export interface PlayerSpecData {
  user_id: number;
  user_email: string;
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
  profile_photo_url: string | null;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface PlayerRepData {
  id: number;
  email: string;
  display_name: string;
  parentesco: { id: number; code: string; description: string } | null;
}

export interface CreatePlayerSpecPayload {
  user_id: number;
  birth_date: string;
  height_cm: number;
  weight_kg: number;
  gender: string;
  entry_date: string;
  medical_conditions?: string | null;
  emergency_contact_name: string;
  emergency_contact_phone: string;
  emergency_contact_relationship: string;
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
  profile_photo_url?: string | null;
  status?: string;
}

export type UpdatePlayerSpecPayload = Partial<CreatePlayerSpecPayload>;

@Injectable({ providedIn: 'root' })
export class UsersHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getPeopleTypes(): Observable<PaginatedResponse<PeopleType>> {
    return this.http.get<PaginatedResponse<PeopleType>>(`${this.configService.apiUrl}/people-types`);
  }

  getParentescos(): Observable<PaginatedResponse<Parentesco>> {
    return this.http.get<PaginatedResponse<Parentesco>>(`${this.configService.apiUrl}/parentescos`);
  }

  getPeople(type: string, page: number, perPage: number, name?: string): Observable<PaginatedResponse<PersonData>> {
    let params: Record<string, string> = { type, page: String(page), perPage: String(perPage) };
    if (name) params['search'] = name;
    return this.http.get<PaginatedResponse<PersonData>>(
      `${this.configService.apiUrl}/people`,
      { params },
    );
  }

  getUsers(role: string, page: number, perPage: number, name?: string, includeDeleted?: boolean, unassigned?: boolean): Observable<PaginatedResponse<UserData>> {
    let params: Record<string, string> = { role, page: String(page), perPage: String(perPage) };
    if (name) params['name'] = name;
    if (includeDeleted) params['includeDeleted'] = '1';
    if (unassigned) params['unassigned'] = '1';
    return this.http.get<PaginatedResponse<UserData>>(
      `${this.configService.apiUrl}/users`,
      { params },
    );
  }

  createUser(data: CreatePersonPayload): Observable<UserData> {
    return this.http.post<UserData>(`${this.configService.apiUrl}/users`, data);
  }

  createAdmin(data: CreateAdminPayload): Observable<UserData> {
    return this.http.post<UserData>(`${this.configService.apiUrl}/users`, data);
  }

  updateUser(id: number, data: UpdatePersonPayload): Observable<UserData> {
    return this.http.put<UserData>(`${this.configService.apiUrl}/users/${id}`, data);
  }

  updateAdmin(id: number, data: UpdateAdminPayload): Observable<UserData> {
    return this.http.put<UserData>(`${this.configService.apiUrl}/users/${id}`, data);
  }

  deleteUser(id: number): Observable<void> {
    return this.http.delete<void>(`${this.configService.apiUrl}/users/${id}`);
  }

  restoreUser(id: number): Observable<UserData> {
    return this.http.put<UserData>(`${this.configService.apiUrl}/users/${id}/restore`, {});
  }

  getPlayerSpec(userId: number): Observable<PlayerSpecData> {
    return this.http.get<PlayerSpecData>(`${this.configService.apiUrl}/player-specifications/${userId}`);
  }

  getPlayerRepresentative(userId: number): Observable<{ data: PlayerRepData | null }> {
    return this.http.get<{ data: PlayerRepData | null }>(`${this.configService.apiUrl}/players/${userId}/representative`);
  }

  createPlayerSpec(data: CreatePlayerSpecPayload): Observable<PlayerSpecData> {
    return this.http.post<PlayerSpecData>(`${this.configService.apiUrl}/player-specifications`, data);
  }

  updatePlayerSpec(userId: number, data: UpdatePlayerSpecPayload): Observable<PlayerSpecData> {
    return this.http.put<PlayerSpecData>(`${this.configService.apiUrl}/player-specifications/${userId}`, data);
  }
}
