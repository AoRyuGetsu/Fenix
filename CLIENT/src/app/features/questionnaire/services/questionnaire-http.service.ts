import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';
import { CatalogService } from '../../../core/services/catalog.service';
import { RoleItem } from '../../../core/auth/services/auth-http.service';

export interface SectionItem {
  id: number;
  title: string;
  description: string | null;
  section_order: number;
  questions: QuestionItem[];
  questions_count: number;
}

export interface QuestionnaireItem {
  id: number;
  title: string;
  description: string | null;
  active: boolean;
  responder_role_id: number;
  responder_role: string;
  target_role_id: number;
  target_role: string;
  sections: SectionItem[];
  questions_count: number;
  created_at: string;
  updated_at: string;
}

export interface QuestionItem {
  id: number;
  text: string;
  type: string;
  question_order: number;
  options?: QuestionOptionItem[];
}

export interface QuestionOptionItem {
  id: number;
  option_text: string;
  option_order: number;
  label?: string | null;
}

export interface QuestionOptionPayload {
  value: string;
  label?: string | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  perPage: number;
}

@Injectable({ providedIn: 'root' })
export class QuestionnaireHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);
  private catalogService = inject(CatalogService);

  getQuestionnaires(page = 1, perPage = 20, title?: string): Observable<PaginatedResponse<QuestionnaireItem>> {
    let params = `page=${page}&perPage=${perPage}`;
    if (title) params += `&title=${encodeURIComponent(title)}`;
    return this.http.get<PaginatedResponse<QuestionnaireItem>>(`${this.configService.apiUrl}/questionnaires?${params}`);
  }

  createQuestionnaire(data: { title: string; description?: string; responder_role_id: number; target_role_id: number }): Observable<QuestionnaireItem> {
    return this.http.post<QuestionnaireItem>(`${this.configService.apiUrl}/questionnaires`, data);
  }

  updateQuestionnaire(id: number, data: { title?: string; description?: string; active?: boolean; responder_role_id?: number; target_role_id?: number }): Observable<QuestionnaireItem> {
    return this.http.put<QuestionnaireItem>(`${this.configService.apiUrl}/questionnaires/${id}`, data);
  }

  deleteQuestionnaire(id: number): Observable<void> {
    return this.http.delete<void>(`${this.configService.apiUrl}/questionnaires/${id}`);
  }

  getSections(questionnaireId: number): Observable<SectionItem[]> {
    return this.http.get<PaginatedResponse<SectionItem>>(
      `${this.configService.apiUrl}/questionnaires/${questionnaireId}/sections?perPage=100`
    ).pipe(map(res => res.data));
  }

  createSection(questionnaireId: number, data: { title: string; description?: string; section_order?: number }): Observable<SectionItem> {
    return this.http.post<SectionItem>(`${this.configService.apiUrl}/questionnaires/${questionnaireId}/sections`, data);
  }

  updateSection(id: number, data: { title?: string; description?: string; section_order?: number }): Observable<SectionItem> {
    return this.http.put<SectionItem>(`${this.configService.apiUrl}/sections/${id}`, data);
  }

  deleteSection(id: number): Observable<void> {
    return this.http.delete<void>(`${this.configService.apiUrl}/sections/${id}`);
  }

  createQuestion(sectionId: number, data: { text: string; type: string; question_order?: number; options?: QuestionOptionPayload[] }): Observable<QuestionItem> {
    return this.http.post<QuestionItem>(`${this.configService.apiUrl}/sections/${sectionId}/questions`, data);
  }

  updateQuestion(id: number, data: { text?: string; type?: string; question_order?: number; options?: QuestionOptionPayload[] }): Observable<QuestionItem> {
    return this.http.put<QuestionItem>(`${this.configService.apiUrl}/questions/${id}`, data);
  }

  deleteQuestion(id: number): Observable<void> {
    return this.http.delete<void>(`${this.configService.apiUrl}/questions/${id}`);
  }

  moveQuestion(id: number, direction: 'up' | 'down'): Observable<void> {
    return this.http.put<void>(`${this.configService.apiUrl}/questions/${id}/move`, { direction });
  }

  getRolesFromCache(): RoleItem[] {
    return this.catalogService.getCatalog<RoleItem>('roles');
  }
}
