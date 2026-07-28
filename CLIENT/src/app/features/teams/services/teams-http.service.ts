import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';
import { PaginatedResponse } from '../../periods/services/periods-http.service';

export interface TeamItem {
  id: number;
  name: string;
  code: string;
  description: string | null;
  trainer: { id: number; email: string; display_name: string };
  player_count: number;
  created_at: string;
  updated_at: string;
}

export interface TeamDetail extends TeamItem {
  players: { id: number; email: string; display_name: string }[];
}

export interface TeamPayload {
  name: string;
  code: string;
  description?: string | null;
  trainer_user_id: number;
  player_ids: number[];
}

@Injectable({ providedIn: 'root' })
export class TeamsHttpService {
  private http = inject(HttpClient);
  private config = inject(ConfigService);

  getAll(page = 1, perPage = 20, search?: string): Observable<PaginatedResponse<TeamItem>> {
    let url = `${this.config.apiUrl}/teams?page=${page}&perPage=${perPage}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    return this.http.get<PaginatedResponse<TeamItem>>(url);
  }

  getById(id: number): Observable<TeamDetail> {
    return this.http.get<TeamDetail>(`${this.config.apiUrl}/teams/${id}`);
  }

  create(payload: TeamPayload): Observable<TeamItem> {
    return this.http.post<TeamItem>(`${this.config.apiUrl}/teams`, payload);
  }

  update(id: number, payload: TeamPayload): Observable<TeamItem> {
    return this.http.put<TeamItem>(`${this.config.apiUrl}/teams/${id}`, payload);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.config.apiUrl}/teams/${id}`);
  }
}
