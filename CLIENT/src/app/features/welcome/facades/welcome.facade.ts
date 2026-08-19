import { Injectable, inject, signal, computed } from '@angular/core';
import { WelcomeHttpService, DashboardStats } from '../services/welcome-http.service';
import { AuthFacade } from '../../../core/auth/facades/auth.facade';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { getErrorMessage } from '../../../shared/utils/error-messages';
import { PlayerDetailModel } from '../../../shared/components/player-detail/player-detail.types';
import { buildPlayerDetail } from '../../../shared/components/player-detail/player-detail.util';

export interface WelcomeState {
  stats: DashboardStats | null;
  loading: boolean;
  error: string | null;
  playerDetail: PlayerDetailModel | null;
  playerDetailLoading: boolean;
}

@Injectable({ providedIn: 'root' })
export class WelcomeFacade {
  private api = inject(WelcomeHttpService);
  private auth = inject(AuthFacade);
  private msg = inject(MessageService);

  private state = signal<WelcomeState>({
    stats: null,
    loading: false,
    error: null,
    playerDetail: null,
    playerDetailLoading: false,
  });

  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  vm = computed(() => ({ ...this.state(), t: this.t }));

  load(): void {
    this.state.update(s => ({ ...s, loading: true, error: null }));

    this.api.getStats().subscribe({
      next: stats => this.state.update(s => ({ ...s, stats, loading: false })),
      error: err => this.state.update(s => ({ ...s, loading: false, error: getErrorMessage(err, 'cr_err_load_dashboard', this.msg) })),
    });
  }

  loadPlayerDetail(): void {
    const user = this.auth.vm().user;
    if (!user) return;

    this.state.update(s => ({ ...s, playerDetail: null, playerDetailLoading: true }));

    this.api.getUser(user.id).subscribe({
      next: u => {
        const displayName = u.person
          ? `${u.person.first_name} ${u.person.first_surname}`.trim()
          : u.email;
        const options = {
          representative: u.representative,
          parentesco: u.representative?.parentesco?.description ?? null,
          team: u.team?.name ?? null,
        };

        this.api.getPlayerSpec(user.id).subscribe({
          next: spec => this.state.update(s => ({
            ...s,
            playerDetail: buildPlayerDetail(user.id, displayName, u.email, u.person, spec, options),
            playerDetailLoading: false,
          })),
          error: () => this.state.update(s => ({
            ...s,
            playerDetail: buildPlayerDetail(user.id, displayName, u.email, u.person, null, options),
            playerDetailLoading: false,
          })),
        });
      },
      error: () => this.state.update(s => ({ ...s, playerDetailLoading: false })),
    });
  }
}