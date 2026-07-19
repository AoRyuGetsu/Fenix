import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';

export interface PeopleType {
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

@Injectable({ providedIn: 'root' })
export class UsersHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getPeopleTypes(): Observable<PaginatedResponse<PeopleType>> {
    return this.http.get<PaginatedResponse<PeopleType>>(`${this.configService.apiUrl}/people-types`);
  }

  getPeople(type: string, page: number, perPage: number, name?: string): Observable<PaginatedResponse<PersonData>> {
    let params: Record<string, string> = { type, page: String(page), perPage: String(perPage) };
    if (name) params['search'] = name;
    return this.http.get<PaginatedResponse<PersonData>>(
      `${this.configService.apiUrl}/people`,
      { params },
    );
  }

  getUsers(role: string, page: number, perPage: number, name?: string, includeDeleted?: boolean): Observable<PaginatedResponse<UserData>> {
    let params: Record<string, string> = { role, page: String(page), perPage: String(perPage) };
    if (name) params['name'] = name;
    if (includeDeleted) params['includeDeleted'] = '1';
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
}
