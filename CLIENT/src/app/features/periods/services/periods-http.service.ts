import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';

export interface PeriodItem {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
}

export interface PeriodPayload {
  name: string;
  start_date: string;
  end_date: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  perPage: number;
}

@Injectable({ providedIn: 'root' })
export class PeriodsHttpService {
  private http = inject(HttpClient);
  private config = inject(ConfigService);
  private baseUrl = '';

  constructor() {
    this.baseUrl = this.config.apiUrl;
  }

  getAll(page = 1, perPage = 20, name?: string): Observable<PaginatedResponse<PeriodItem>> {
    let url = `${this.baseUrl}/periods?page=${page}&perPage=${perPage}`;
    if (name) url += `&name=${encodeURIComponent(name)}`;
    return this.http.get<PaginatedResponse<PeriodItem>>(url);
  }

  create(payload: PeriodPayload): Observable<PeriodItem> {
    return this.http.post<PeriodItem>(`${this.baseUrl}/periods`, payload);
  }

  update(id: number, payload: PeriodPayload): Observable<PeriodItem> {
    return this.http.put<PeriodItem>(`${this.baseUrl}/periods/${id}`, payload);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/periods/${id}`);
  }
}
