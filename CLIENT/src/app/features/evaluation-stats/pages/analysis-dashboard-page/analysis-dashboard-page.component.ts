import { Component, OnInit, inject } from '@angular/core';
import { AuthFacade } from '../../../../core/auth/facades/auth.facade';
import { EvaluationStatsFacade } from '../../facades/evaluation-stats.facade';
import { PlayerListComponent } from '../../../../shared/components/player-list/player-list.component';
import { PlayerStatsPanelComponent } from '../../components/player-stats-panel/player-stats-panel.component';

@Component({
  selector: 'app-analysis-dashboard-page',
  standalone: true,
  imports: [PlayerListComponent, PlayerStatsPanelComponent],
  templateUrl: './analysis-dashboard-page.component.html',
})
export class AnalysisDashboardPageComponent implements OnInit {
  auth = inject(AuthFacade);
  facade = inject(EvaluationStatsFacade);
  vm = this.facade.dashboardVm;

  ngOnInit(): void {
    const roles = this.auth.authRoles();
    if (roles.includes('JUG')) {
      this.facade.loadMyAnalysis();
    } else if (roles.includes('REP')) {
      this.facade.loadRepresentedPlayers();
    } else if (roles.includes('ENT')) {
      this.facade.loadMyTeam();
    } else {
      this.facade.loadAllPlayers();
    }
  }

  onSelectPlayer(id: number): void {
    this.facade.selectPlayer(id);
  }

  onChangePage(page: number): void {
    this.facade.changeRosterPage(page);
  }

  onSearch(term: string): void {
    this.facade.setRosterSearch(term);
  }
}