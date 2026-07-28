import { Component, input, output, signal, computed, effect } from '@angular/core';
import { TeamDetail, TeamPayload } from '../../services/teams-http.service';
import { MessagePipe } from '../../../../shared/pipes/message.pipe';

@Component({
  selector: 'app-team-form-modal',
  standalone: true,
  imports: [MessagePipe],
  templateUrl: './team-form-modal.component.html',
})
export class TeamFormModalComponent {
  item = input<TeamDetail | null>(null);
  saving = input(false);
  trainers = input<{ id: number; display_name: string }[]>([]);
  players = input<{ id: number; display_name: string }[]>([]);
  loadingTrainers = input(false);
  loadingPlayers = input(false);

  save = output<TeamPayload>();
  close = output<void>();

  name = signal('');
  code = signal('');
  description = signal('');
  trainerUserId = signal<number | null>(null);
  playerIds = signal<number[]>([]);

  isEdit = computed(() => this.item() !== null);

  constructor() {
    effect(() => {
      const i = this.item();
      if (i) {
        this.name.set(i.name);
        this.code.set(i.code);
        this.description.set(i.description ?? '');
        this.trainerUserId.set(i.trainer.id);
        this.playerIds.set(i.players.map(p => p.id));
      }
    });
  }

  onNameChange(event: Event): void {
    this.name.set((event.target as HTMLInputElement).value);
  }

  onCodeChange(event: Event): void {
    this.code.set((event.target as HTMLInputElement).value);
  }

  onDescriptionChange(event: Event): void {
    this.description.set((event.target as HTMLTextAreaElement).value);
  }

  onTrainerChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    this.trainerUserId.set(target.value ? Number(target.value) : null);
  }

  onPlayerToggle(playerId: number): void {
    this.playerIds.update(ids =>
      ids.includes(playerId) ? ids.filter(id => id !== playerId) : [...ids, playerId]
    );
  }

  onSubmit(): void {
    if (!this.name() || !this.code() || this.trainerUserId() === null) return;

    this.save.emit({
      name: this.name(),
      code: this.code(),
      description: this.description() || null,
      trainer_user_id: this.trainerUserId()!,
      player_ids: this.playerIds(),
    });
  }

  onClose(): void {
    this.close.emit();
  }
}
