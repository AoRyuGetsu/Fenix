import { Injectable, inject, signal } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import {
  UsersHttpService,
  PeopleType,
  PersonData,
  UserData,
  PaginatedResponse,
  CreatePersonPayload,
  UpdatePersonPayload,
  CreateAdminPayload,
  UpdateAdminPayload,
} from '../services/users-http.service';

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
}

const EMPTY_PAGINATED = { data: [], total: 0, page: 1, perPage: 20 };

@Injectable({ providedIn: 'root' })
export class UsersFacade {
  private api = inject(UsersHttpService);

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
    this.state.update(s => ({ ...s, activeTab: code, activePage: 1, searchTerm: '', error: null }));
    this.loadTabData(code);
  }

  setSearchTerm(term: string): void {
    this.state.update(s => ({ ...s, searchTerm: term, activePage: 1 }));
    this.loadTabData(this.state().activeTab, 1);
  }

  loadTabData(tab: string, page: number = 1): void {
    const searchTerm = this.state().searchTerm;
    this.state.update(s => ({ ...s, loading: true, error: null }));

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

  changePage(page: number): void {
    if (page < 1) return;
    this.loadTabData(this.state().activeTab, page);
  }

  openCreateModal(): void {
    this.state.update(s => ({ ...s, showCreateModal: true }));
  }

  openUpdateModal(item: PersonData | UserData): void {
    this.state.update(s => ({ ...s, showUpdateModal: true, selectedItem: item }));
  }

  closeModals(): void {
    this.state.update(s => ({ ...s, showCreateModal: false, showUpdateModal: false, selectedItem: null }));
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
