import { Component, OnInit, inject, computed } from '@angular/core';
import { AuthFacade } from '../../../../core/auth/facades/auth.facade';
import { WelcomeFacade } from '../../facades/welcome.facade';
import { PlayerDetailCardsComponent } from '../../../../shared/components/player-detail/player-detail-cards.component';

@Component({
  selector: 'app-welcome-page',
  standalone: true,
  imports: [PlayerDetailCardsComponent],
  templateUrl: './welcome-page.html',
})
export class WelcomePageComponent implements OnInit {
  auth = inject(AuthFacade);
  facade = inject(WelcomeFacade);

  vm = computed(() => ({ ...this.facade.vm(), roles: this.auth.vm().roles }));

  showEvaluationStats = computed(() => !this.auth.vm().roles.includes('REP'));

  showPlayerCounts = computed(() => {
    const roles = this.auth.vm().roles;
    return !roles.includes('JUG') && !roles.includes('REP');
  });

  ngOnInit(): void {
    this.facade.load();
    if (this.auth.vm().roles.includes('JUG')) {
      this.facade.loadPlayerDetail();
    }
  }
}
