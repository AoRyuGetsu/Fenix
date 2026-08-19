import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { TeamDetail, TeamPayload } from '../../services/teams-http.service';
import { MessageService, MessageResolver } from '../../../../core/services/message.service';

@Component({
  selector: 'app-team-form-modal',
  standalone: true,
  templateUrl: './team-form-modal.component.html',
})
export class TeamFormModalComponent {
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

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

  vm = computed(() => ({
    item: this.item(),
    saving: this.saving(),
    trainers: this.trainers(),
    players: this.players(),
    loadingTrainers: this.loadingTrainers(),
    loadingPlayers: this.loadingPlayers(),
    name: this.name(),
    code: this.code(),
    description: this.description(),
    trainerUserId: this.trainerUserId(),
    playerIds: this.playerIds(),
    isEdit: this.isEdit(),
    t: this.t,
  }));

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
