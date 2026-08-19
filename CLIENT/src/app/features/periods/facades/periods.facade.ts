import { Injectable, inject, signal, computed } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
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
  private msg = inject(MessageService);

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

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.state(), t: this.t }));

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
        error: getErrorMessage(err, 'per_err_load', this.msg),
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
          success: editing ? this.msg.get('per_suc_updated') : this.msg.get('per_suc_created'),
        }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: (err) => this.state.update(st => ({
        ...st,
        saving: false,
        error: getErrorMessage(err, editing ? 'per_err_update' : 'per_err_create', this.msg),
      })),
    });
  }

  deletePeriod(id: number): void {
    if (!confirm(this.msg.get('per_cnf_delete'))) return;

    this.state.update(st => ({ ...st, loading: true, error: null, success: null }));

    this.api.delete(id).subscribe({
      next: () => {
        this.state.update(st => ({
          ...st,
          loading: false,
          success: this.msg.get('per_suc_deleted'),
        }));
        this.load();
        this.clearSuccessAfterDelay();
      },
      error: (err) => this.state.update(st => ({
        ...st,
        loading: false,
        error: getErrorMessage(err, 'per_err_delete', this.msg),
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
