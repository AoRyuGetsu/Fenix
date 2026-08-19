import { Injectable, inject, signal, computed } from '@angular/core';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { AuthFacade } from '../../../core/auth/facades/auth.facade';
import {
  RepresentadosHttpService,
  RepresentadoItem,
  PaginatedResponse,
} from '../services/representados-http.service';
import { PlayerDetailModel } from '../../../shared/components/player-detail/player-detail.types';
import { buildPlayerDetail } from '../../../shared/components/player-detail/player-detail.util';

export interface RepresentadosState {
  items: RepresentadoItem[];
  total: number;
  page: number;
  perPage: number;
  search: string;
  loading: boolean;
  error: string | null;
  selectedId: number | null;
}

@Injectable({ providedIn: 'root' })
export class RepresentadosFacade {
  private api = inject(RepresentadosHttpService);
  private msg = inject(MessageService);
  private auth = inject(AuthFacade);

  private state = signal<RepresentadosState>({
    items: [],
    total: 0,
    page: 1,
    perPage: 20,
    search: '',
    loading: false,
    error: null,
    selectedId: null,
  });

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.state(), t: this.t }));

  selectedItem = computed(
    () => this.vm().items.find(i => i.id === this.vm().selectedId) ?? null,
  );

  selectedModel = computed<PlayerDetailModel | null>(() => {
    const item = this.selectedItem();
    if (!item) return null;
    const user = this.auth.vm().user;
    const representative = user
      ? { id: user.id, display_name: user.person?.full_name ?? user.email, email: user.email }
      : null;
    return buildPlayerDetail(item.id, item.display_name, item.email, item.person, item.player_spec, {
      parentesco: item.parentesco?.description ?? null,
      representative,
    });
  });

  load(page?: number, search?: string): void {
    const s = this.state();
    const p = page ?? s.page;
    const term = search ?? s.search;
    this.state.update(st => ({ ...st, loading: true, error: null }));

    this.api.getMyPlayers(p, s.perPage, term).subscribe({
      next: (res: PaginatedResponse<RepresentadoItem>) =>
        this.state.update(st => {
          const items = res.data;
          const current = st.selectedId;
          const exists = current !== null && items.some(i => i.id === current);
          return {
            ...st,
            items,
            total: res.total,
            page: res.page,
            perPage: res.perPage,
            selectedId: exists ? current : (items[0]?.id ?? null),
            loading: false,
          };
        }),
      error: err =>
        this.state.update(st => ({
          ...st,
          loading: false,
          error: getErrorMessage(err, 'rep_err_load', this.msg),
        })),
    });
  }

  select(id: number): void {
    this.state.update(st => ({ ...st, selectedId: id }));
  }

  setSearch(search: string): void {
    this.state.update(st => ({ ...st, search }));
    this.load(1, search);
  }

  changePage(page: number): void {
    this.load(page);
  }

  clearMessages(): void {
    this.state.update(st => ({ ...st, error: null }));
  }
}