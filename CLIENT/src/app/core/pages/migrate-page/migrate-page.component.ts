import { Component, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs';
import { ConfigService } from '../../services/config.service';
import { MessageService, MessageResolver } from '../../services/message.service';

@Component({
  selector: 'app-migrate-page',
  templateUrl: './migrate-page.component.html',
})
export class MigratePageComponent {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);
  private msg = inject(MessageService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  private loading = signal(false);
  private output = signal<string | null>(null);
  private error = signal<string | null>(null);

  vm = computed(() => ({
    loading: this.loading(),
    output: this.output(),
    error: this.error(),
    t: this.t,
  }));

  run(): void {
    this.loading.set(true);
    this.output.set(null);
    this.error.set(null);

    this.http.get(`${this.configService.apiUrl}/migrate.php`, { responseType: 'text' }).pipe(
      tap({
        next: (res) => {
          this.output.set(res);
          this.loading.set(false);
        },
        error: (err) => {
          this.error.set(err.status === 403
            ? this.t('cr_err_migration_token')
            : this.t('cr_err_migration_failed', { status: err.status })
          );
          this.loading.set(false);
        },
      }),
    ).subscribe();
  }
}
