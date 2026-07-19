import { Component, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs';
import { ConfigService } from '../../services/config.service';

@Component({
  selector: 'app-migrate-page',
  imports: [],
  templateUrl: './migrate-page.component.html',
})
export class MigratePageComponent {
  private http = inject(HttpClient);
  private configService = inject(ConfigService);

  loading = signal(false);
  output = signal<string | null>(null);
  error = signal<string | null>(null);

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
            ? 'Token de migración inválido. Define MIGRATION_TOKEN en el .env'
            : `Error al ejecutar migraciones (${err.status})`
          );
          this.loading.set(false);
        },
      }),
    ).subscribe();
  }
}
