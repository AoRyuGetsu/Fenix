import { Injectable, inject, signal } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { TeamsHttpService, TeamDetail, TeamItem, TeamPayload } from '../services/teams-http.service';
import { UsersHttpService } from '../../users/services/users-http.service';

export interface TeamsState {
  items: TeamItem[];
  total: number;
  page: number;
  perPage: number;
  loading: boolean;
  saving: boolean;
  error: string | null;
  success: string | null;
  searchTerm: string;
  showFormModal: boolean;
  editingItem: TeamDetail | null;
  trainers: { id: number; display_name: string }[];
  players: { id: number; display_name: string }[];
  loadingTrainers: boolean;
  loadingPlayers: boolean;
}

@Injectable({ providedIn: 'root' })
export class TeamsFacade {
  private api = inject(TeamsHttpService);
  private usersApi = inject(UsersHttpService);

  private state = signal<TeamsState>({
    items: [],
    total: 0,
    page: 1,
    perPage: 20,
    loading: false,
    saving: false,
    error: null,
    success: null,
    searchTerm: '',
    showFormModal: false,
    editingItem: null,
    trainers: [],
    players: [],
    loadingTrainers: false,
    loadingPlayers: false,
  });

  vm = this.state.asReadonly();

  load(page?: number): void {
    const s = this.state();
    const p = page ?? s.page;
    this.state.update(st => ({ ...st, loading: true, error: null, success: null }));

    this.api.getAll(p, s.perPage, s.searchTerm || undefined).subscribe({
      next: res => this.state.update(st => ({
        ...st,
        items: res.data,
        total: res.total,
        page: res.page,
        perPage: res.perPage,
        loading: false,
      })),
      error: err => this.state.update(st => ({
        ...st,
        loading: false,
        error: getErrorMessage(err, 'Error al cargar equipos'),
      })),
    });
  }

  setSearchTerm(term: string): void {
    this.state.update(s => ({ ...s, searchTerm: term, page: 1 }));
    this.load(1);
  }

  changePage(page: number): void {
    this.load(page);
  }

  delete(id: number): void {
    this.api.delete(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, success: 'Equipo eliminado correctamente' }));
        this.load();
      },
      error: err => this.state.update(s => ({
        ...s,
        error: getErrorMessage(err, 'Error al eliminar equipo'),
      })),
    });
  }

  clearMessages(): void {
    this.state.update(s => ({ ...s, error: null, success: null }));
  }

  openCreate(): void {
    this.state.update(s => ({
      ...s,
      showFormModal: true,
      editingItem: null,
      loadingTrainers: true,
      loadingPlayers: true,
    }));
    this.loadFormData();
  }

  openEdit(id: number): void {
    this.state.update(s => ({
      ...s,
      showFormModal: true,
      editingItem: null,
      loadingTrainers: true,
      loadingPlayers: true,
    }));

    this.api.getById(id).subscribe({
      next: detail => {
        this.state.update(s => ({ ...s, editingItem: detail }));
      },
    });

    this.loadFormData();
  }

  closeForm(): void {
    this.state.update(s => ({
      ...s,
      showFormModal: false,
      editingItem: null,
      trainers: [],
      players: [],
    }));
  }

  saveTeam(payload: TeamPayload): void {
    const editing = this.state().editingItem;
    this.state.update(s => ({ ...s, saving: true, error: null }));

    const request = editing
      ? this.api.update(editing.id, payload)
      : this.api.create(payload);

    request.subscribe({
      next: () => {
        this.state.update(s => ({
          ...s,
          saving: false,
          showFormModal: false,
          editingItem: null,
          trainers: [],
          players: [],
          success: editing
            ? 'Equipo actualizado correctamente'
            : 'Equipo creado correctamente',
        }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          saving: false,
          error: getErrorMessage(err, 'Error al guardar equipo'),
        }));
      },
    });
  }

  private loadFormData(): void {
    this.usersApi.getUsers('ENT', 1, 500).subscribe({
      next: res => {
        const trainers = res.data.map(u => ({
          id: u.id,
          display_name: u.person
            ? (u.person.first_name + ' ' + u.person.first_surname).trim()
            : u.email,
        }));
        this.state.update(s => ({ ...s, trainers, loadingTrainers: false }));
      },
      error: () => this.state.update(s => ({ ...s, loadingTrainers: false })),
    });

    this.usersApi.getUsers('JUG', 1, 500, undefined, undefined, true).subscribe({
      next: res => {
        const players = res.data.map(u => ({
          id: u.id,
          display_name: u.person
            ? (u.person.first_name + ' ' + u.person.first_surname).trim()
            : u.email,
        }));
        this.state.update(s => ({ ...s, players, loadingPlayers: false }));
      },
      error: () => this.state.update(s => ({ ...s, loadingPlayers: false })),
    });
  }

  private clearSuccessAfterDelay(): void {
    setTimeout(() => {
      this.state.update(s => ({ ...s, success: null }));
    }, 3000);
  }
}
