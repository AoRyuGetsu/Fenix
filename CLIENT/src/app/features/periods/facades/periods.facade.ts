import { Injectable, inject, signal } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import {
  PeriodsHttpService,
  PeriodItem,
  PeriodPayload,
} from '../services/periods-http.service';


export interface PeriodsState {
  items: PeriodItem[];
  total: number;
  page: number;
  perPage: number;
  loading: boolean;
  saving: boolean;
  error: string | null;
  success: string | null;
  showFormModal: boolean;
  editingItem: PeriodItem | null;
  filterName: string;
}

@Injectable({ providedIn: 'root' })
export class PeriodsFacade {
  private api = inject(PeriodsHttpService);

  private state = signal<PeriodsState>({
    items: [],
    total: 0,
    page: 1,
    perPage: 20,
    loading: false,
    saving: false,
    error: null,
    success: null,
    showFormModal: false,
    editingItem: null,
    filterName: '',
  });

  vm = this.state.asReadonly();

  load(page?: number): void {
    const s = this.state();
    const p = page ?? s.page;
    this.state.update(st => ({ ...st, loading: true, error: null, success: null }));

    this.api.getAll(p, s.perPage, s.filterName || undefined).subscribe({
      next: (res) => this.state.update(st => ({
        ...st,
        items: res.data,
        total: res.total,
        page: res.page,
        perPage: res.perPage,
        loading: false,
      })),
      error: (err) => this.state.update(st => ({
        ...st,
        loading: false,
        error: getErrorMessage(err, 'Error al cargar periodos'),
      })),
    });
  }

  setFilter(name: string): void {
    this.state.update(st => ({ ...st, filterName: name }));
    this.load(1);
  }

  changePage(page: number): void {
    this.load(page);
  }

  openCreate(): void {
    this.state.update(st => ({ ...st, showFormModal: true, editingItem: null, error: null }));
  }

  openEdit(item: PeriodItem): void {
    this.state.update(st => ({ ...st, showFormModal: true, editingItem: item, error: null }));
  }

  closeForm(): void {
    this.state.update(st => ({ ...st, showFormModal: false, editingItem: null }));
  }

  save(payload: PeriodPayload): void {
    const editing = this.state().editingItem;
    this.state.update(st => ({ ...st, saving: true, error: null, success: null }));

    const request = editing
      ? this.api.update(editing.id, payload)
      : this.api.create(payload);

    request.subscribe({
      next: () => {
        this.state.update(st => ({
          ...st,
          saving: false,
          showFormModal: false,
          editingItem: null,
          success: editing ? 'Periodo actualizado correctamente' : 'Periodo creado correctamente',
        }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: (err) => this.state.update(st => ({
        ...st,
        saving: false,
        error: getErrorMessage(err, editing ? 'Error al actualizar periodo' : 'Error al crear periodo'),
      })),
    });
  }

  deletePeriod(id: number): void {
    if (!confirm('¿Está seguro de eliminar este periodo?')) return;

    this.state.update(st => ({ ...st, loading: true, error: null, success: null }));

    this.api.delete(id).subscribe({
      next: () => {
        this.state.update(st => ({
          ...st,
          loading: false,
          success: 'Periodo eliminado correctamente',
        }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: (err) => this.state.update(st => ({
        ...st,
        loading: false,
        error: getErrorMessage(err, 'Error al eliminar periodo'),
      })),
    });
  }

  clearMessages(): void {
    this.state.update(st => ({ ...st, error: null, success: null }));
  }

  private clearSuccessAfterDelay(): void {
    setTimeout(() => {
      this.state.update(st => ({ ...st, success: null }));
    }, 3000);
  }
}
