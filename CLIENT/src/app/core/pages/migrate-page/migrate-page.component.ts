import { Component, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { isDevMode } from '@angular/core';
import { tap } from 'rxjs';

const BASE_URL = isDevMode() ? 'http://localhost:8000' : '';

@Component({
  selector: 'app-migrate-page',
  imports: [],
  templateUrl: './migrate-page.component.html',
})
export class MigratePageComponent {
  private http = inject(HttpClient);

  loading = signal(false);
  output = signal<string | null>(null);
  error = signal<string | null>(null);

  run(): void {
    this.loading.set(true);
    this.output.set(null);
    this.error.set(null);

    this.http.get(`${BASE_URL}/migrate.php`, { responseType: 'text' }).pipe(
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
