import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { ConfigService } from '../../../core/services/config.service';
import { PaginatedResponse } from '../../questionnaire/services/questionnaire-http.service';

export interface SectionItem {
  id: number;
  title: string;
  description: string | null;
  section_order: number;
  questions: QuestionItem[];
  questions_count: number;
}

export interface MyQuestionnaireItem {
  id: number;
  title: string;
  description: string | null;
  active: boolean;
  responder_role_id: number;
  responder_role: string;
  target_role_id: number;
  target_role: string;
  questions_count: number;
  created_at: string;
  updated_at: string;
}

export interface QuestionnaireDetail {
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
}

export interface UserItem {
  id: number;
  email: string;
  active: boolean;
  roles: string[];
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
  representative: {
    id: number;
    display_name: string;
    email: string;
  } | null;
  display_name?: string;
  created_at: string;
  updated_at: string;
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
  jersey_number: number | null;
  dominant_foot: string | null;
  nationality: string | null;
  blood_type: string | null;
  allergies: string | null;
  profile_photo_url: string | null;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface PeriodItem {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
}

export interface CreateEvaluationRequest {
  questionnaire_id: number;
  target_user_id: number;
  period_id: number;
  answers: { question_id: number; value: string }[];
}

export interface UpdateEvaluationRequest {
  answers: { question_id: number; value: string }[];
}

export interface PeriodTargetsData {
  unevaluated: UserItem[];
  evaluated: { evaluation_id: number; user: UserItem; answers: { id: number; question_id: number; value: string }[] }[];
}

export interface PeriodTargetsResponse {
  data: PeriodTargetsData;
  total: number;
  page: number;
  perPage: number;
}

export interface EvaluationResponse {
  id: number;
  questionnaire_id: number;
  questionnaire_title: string;
  responder_user_id: number;
  responder_email: string;
  target_user_id: number;
  target_email: string;
  answers: {
    id: number;
    question_id: number;
    question_text: string;
    question_type: string;
    value: string;
  }[];
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class EvaluationsHttpService {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  getMyQuestionnaires(page = 1, perPage = 20, title?: string): Observable<PaginatedResponse<MyQuestionnaireItem>> {
    let params = `page=${page}&perPage=${perPage}`;
    if (title) params += `&title=${encodeURIComponent(title)}`;
    return this.http.get<PaginatedResponse<MyQuestionnaireItem>>(`${this.configService.apiUrl}/evaluations/my-questionnaires?${params}`);
  }

  createEvaluation(data: CreateEvaluationRequest): Observable<EvaluationResponse> {
    return this.http.post<EvaluationResponse>(`${this.configService.apiUrl}/evaluations`, data);
  }

  updateEvaluation(id: number, data: UpdateEvaluationRequest): Observable<EvaluationResponse> {
    return this.http.put<EvaluationResponse>(`${this.configService.apiUrl}/evaluations/${id}`, data);
  }

  getQuestionnaire(id: number): Observable<QuestionnaireDetail> {
    return this.http.get<QuestionnaireDetail>(`${this.configService.apiUrl}/questionnaires/${id}`);
  }

  getPeriods(): Observable<PeriodItem[]> {
    return this.http.get<{ data: PeriodItem[] }>(`${this.configService.apiUrl}/periods`)
      .pipe(map(res => res.data));
  }

  getPlayerSpec(userId: number): Observable<PlayerSpecData> {
    return this.http.get<PlayerSpecData>(`${this.configService.apiUrl}/player-specifications/${userId}`);
  }

  getPeriodTargets(periodId: number, questionnaireId: number, page = 1, perPage = 20): Observable<PeriodTargetsResponse> {
    return this.http.get<PeriodTargetsResponse>(
      `${this.configService.apiUrl}/evaluations/period-targets?period_id=${periodId}&questionnaire_id=${questionnaireId}&page=${page}&perPage=${perPage}`
    );
  }
}
