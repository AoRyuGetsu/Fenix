import { Injectable, inject, signal } from '@angular/core';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import {
  UsersHttpService,
  PeopleType,
  PersonData,
  UserData,
  PlayerSpecData,
  CreatePlayerSpecPayload,
  UpdatePlayerSpecPayload,
  PaginatedResponse,
  CreatePersonPayload,
  UpdatePersonPayload,
  CreateAdminPayload,
  UpdateAdminPayload,
} from '../services/users-http.service';
import { TeamsHttpService, TeamItem } from '../../teams/services/teams-http.service';

export interface TabItem {
  code: string;
  label: string;
}

export interface UsersState {
  tabs: TabItem[];
  activeTab: string;
  people: Record<string, PaginatedResponse<PersonData>>;
  admins: PaginatedResponse<UserData>;
  loading: boolean;
  error: string | null;
  success: string | null;
  showCreateModal: boolean;
  showUpdateModal: boolean;
  selectedItem: PersonData | UserData | null;
  activePage: number;
  totalPages: number;
  searchTerm: string;
  showDeleted: boolean;
  teams: TeamItem[];
  representatives: UserData[];
  loadingTeams: boolean;
  loadingReps: boolean;
  showSpecModal: boolean;
  specUserId: number | null;
  specData: PlayerSpecData | null;
  specLoading: boolean;
  playerSpecs: Record<number, PlayerSpecData>;
}

const EMPTY_PAGINATED = { data: [], total: 0, page: 1, perPage: 20 };

@Injectable({ providedIn: 'root' })
export class UsersFacade {
  private api = inject(UsersHttpService);
  private teamsApi = inject(TeamsHttpService);

  private state = signal<UsersState>({
    tabs: [],
    activeTab: '',
    people: {},
    admins: EMPTY_PAGINATED,
    loading: false,
    error: null,
    success: null,
    showCreateModal: false,
    showUpdateModal: false,
    selectedItem: null,
    activePage: 1,
    totalPages: 0,
    searchTerm: '',
    showDeleted: false,
    teams: [],
    representatives: [],
    loadingTeams: false,
    loadingReps: false,
    showSpecModal: false,
    specUserId: null,
    specData: null,
    specLoading: false,
    playerSpecs: {},
  });

  vm = this.state.asReadonly();

  loadTabs(): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.getPeopleTypes().subscribe({
      next: types => {
        const tabs: TabItem[] = types.data.filter(t => t.code !== 'ADM').map(t => ({ code: t.code, label: t.description }));
        tabs.push({ code: 'admin', label: 'Administradores' });
        const activeTab = tabs.length > 1 ? tabs[0].code : 'admin';
        this.state.update(s => ({ ...s, tabs, activeTab, loading: false }));
        this.loadTabData(activeTab);
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al cargar tipos de persona'),
        }));
      },
    });
  }

  switchTab(code: string): void {
    if (this.state().activeTab === code) return;
    this.state.update(s => ({ ...s, activeTab: code, activePage: 1, searchTerm: '', error: null, playerSpecs: {} }));
    this.loadTabData(code);
  }

  setSearchTerm(term: string): void {
    this.state.update(s => ({ ...s, searchTerm: term, activePage: 1 }));
    this.loadTabData(this.state().activeTab, 1);
  }

  loadTabData(tab: string, page: number = 1): void {
    const searchTerm = this.state().searchTerm;
    this.state.update(s => ({ ...s, loading: true, error: null, playerSpecs: {} }));

    if (tab === 'admin') {
      const includeDeleted = this.state().showDeleted || undefined;
      this.api.getUsers('ADM', page, 20, searchTerm || undefined, includeDeleted).subscribe({
        next: res => {
          this.state.update(s => ({
            ...s,
            admins: res,
            loading: false,
            activePage: page,
            totalPages: Math.ceil(res.total / res.perPage),
          }));
        },
        error: err => {
          this.state.update(s => ({
            ...s,
            loading: false,
            error: getErrorMessage(err, 'Error al cargar administradores'),
          }));
        },
      });
    } else {
      this.api.getPeople(tab, page, 20, searchTerm || undefined).subscribe({
        next: res => {
          this.state.update(s => ({
            ...s,
            people: { ...s.people, [tab]: res },
            loading: false,
            activePage: page,
            totalPages: Math.ceil(res.total / res.perPage),
          }));
          this.loadPlayerSpecs(res.data);
        },
        error: err => {
          this.state.update(s => ({
            ...s,
            loading: false,
            error: getErrorMessage(err, 'Error al cargar personas'),
          }));
        },
      });
    }
  }

  private loadPlayerSpecs(people: PersonData[]): void {
    const userIds = people
      .map(p => p.user_id)
      .filter((id): id is number => id !== null);

    if (userIds.length === 0) return;

    forkJoin(
      userIds.map(id =>
        this.api.getPlayerSpec(id).pipe(catchError(() => of(null)))
      )
    ).subscribe(results => {
      const specsMap: Record<number, PlayerSpecData> = {};
      userIds.forEach((id, i) => {
        if (results[i]) specsMap[id] = results[i]!;
      });
      this.state.update(s => ({ ...s, playerSpecs: specsMap }));
    });
  }

  changePage(page: number): void {
    if (page < 1) return;
    this.loadTabData(this.state().activeTab, page);
  }

  openCreateModal(): void {
    this.state.update(s => ({ ...s, showCreateModal: true }));
    this.loadTeamsAndReps();
  }

  openUpdateModal(item: PersonData | UserData): void {
    this.state.update(s => ({ ...s, showUpdateModal: true, selectedItem: item }));
    this.loadTeamsAndReps();
  }

  closeModals(): void {
    this.state.update(s => ({ ...s, showCreateModal: false, showUpdateModal: false, selectedItem: null }));
  }

  private loadTeamsAndReps(): void {
    this.state.update(s => ({ ...s, loadingTeams: true, loadingReps: true }));

    this.teamsApi.getAll(1, 500).subscribe({
      next: res => {
        this.state.update(s => ({ ...s, teams: res.data, loadingTeams: false }));
      },
      error: () => {
        this.state.update(s => ({ ...s, loadingTeams: false }));
      },
    });

    this.api.getUsers('REP', 1, 500).subscribe({
      next: res => {
        const reps = res.data.map(u => ({
          ...u,
          display_name: u.person
            ? (u.person.first_name + ' ' + u.person.first_surname).trim()
            : u.email,
        }));
        this.state.update(s => ({ ...s, representatives: reps, loadingReps: false }));
      },
      error: () => {
        this.state.update(s => ({ ...s, loadingReps: false }));
      },
    });
  }

  createPerson(data: CreatePersonPayload): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));
    this.api.createUser(data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showCreateModal: false, success: 'Persona creada correctamente' }));
        this.loadTabData(this.state().activeTab, this.state().activePage);
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al crear persona'),
        }));
      },
    });
  }

  createAdmin(data: CreateAdminPayload): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));
    this.api.createAdmin(data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showCreateModal: false, success: 'Administrador creado correctamente' }));
        this.loadTabData(this.state().activeTab, this.state().activePage);
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al crear administrador'),
        }));
      },
    });
  }

  updateUser(id: number, data: UpdatePersonPayload): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));
    this.api.updateUser(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showUpdateModal: false, selectedItem: null, success: 'Persona actualizada correctamente' }));
        this.loadTabData(this.state().activeTab, this.state().activePage);
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al actualizar persona'),
        }));
      },
    });
  }

  updateAdmin(id: number, data: UpdateAdminPayload): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));
    this.api.updateAdmin(id, data).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, showUpdateModal: false, selectedItem: null, success: 'Administrador actualizado correctamente' }));
        this.loadTabData(this.state().activeTab, this.state().activePage);
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al actualizar administrador'),
        }));
      },
    });
  }

  deleteUser(id: number): void {
    if (!confirm('¿Está seguro de eliminar este usuario?')) return;

    this.state.update(s => ({ ...s, loading: true, error: null }));
    this.api.deleteUser(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, success: 'Usuario eliminado correctamente' }));
        this.loadTabData(this.state().activeTab, this.state().activePage);
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al eliminar usuario'),
        }));
      },
    });
  }

  private clearSuccessAfterDelay(): void {
    setTimeout(() => {
      this.state.update(s => ({ ...s, success: null }));
    }, 3000);
  }

  openSpecModal(personId: number): void {
    this.state.update(s => ({ ...s, specLoading: true, specUserId: null, specData: null, error: null }));

    const person = this.findPersonById(personId);
    const userId = person?.user_id;
    if (!userId) {
      this.state.update(s => ({ ...s, specLoading: false, showSpecModal: true, specUserId: null }));
      return;
    }

    this.api.getPlayerSpec(userId).subscribe({
      next: spec => {
        this.state.update(s => ({ ...s, specLoading: false, showSpecModal: true, specUserId: userId, specData: spec }));
      },
      error: () => {
        this.state.update(s => ({ ...s, specLoading: false, showSpecModal: true, specUserId: userId, specData: null }));
      },
    });
  }

  closeSpecModal(): void {
    this.state.update(s => ({ ...s, showSpecModal: false, specUserId: null, specData: null, specLoading: false }));
  }

  saveSpec(data: CreatePlayerSpecPayload): void {
    const userId = this.state().specUserId;
    const isUpdate = this.state().specData !== null;

    this.state.update(s => ({ ...s, specLoading: true, error: null }));

    const request = isUpdate && userId
      ? this.api.updatePlayerSpec(userId, data as UpdatePlayerSpecPayload)
      : this.api.createPlayerSpec(data);

    request.subscribe({
      next: () => {
        this.state.update(s => ({
          ...s,
          specLoading: false,
          showSpecModal: false,
          specUserId: null,
          specData: null,
          success: 'Ficha de jugador guardada correctamente',
        }));
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          specLoading: false,
          error: getErrorMessage(err, 'Error al guardar ficha de jugador'),
        }));
      },
    });
  }

  private findPersonById(id: number): PersonData | undefined {
    const people = this.state().people;
    for (const key of Object.keys(people)) {
      const found = people[key].data.find(p => p.id === id);
      if (found) return found;
    }
    return undefined;
  }

  toggleShowDeleted(): void {
    const newVal = !this.state().showDeleted;
    this.state.update(s => ({ ...s, showDeleted: newVal, activePage: 1 }));
    this.loadTabData(this.state().activeTab, 1);
  }

  restoreUser(id: number): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));
    this.api.restoreUser(id).subscribe({
      next: () => {
        this.state.update(s => ({ ...s, loading: false, success: 'Usuario restaurado correctamente' }));
        this.loadTabData(this.state().activeTab, this.state().activePage);
        this.clearSuccessAfterDelay();
      },
      error: err => {
        this.state.update(s => ({
          ...s,
          loading: false,
          error: getErrorMessage(err, 'Error al restaurar usuario'),
        }));
      },
    });
  }
}
