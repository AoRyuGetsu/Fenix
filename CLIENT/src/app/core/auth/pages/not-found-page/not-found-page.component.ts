import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-not-found-page',
  imports: [RouterModule],
  template: `
    <div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
      <div class="text-center px-3">
        <div class="display-1 fw-bold text-white mb-2" style="font-size: 6rem; line-height: 1;">404</div>
        <p class="text-white-50 mb-4" style="font-size: 1.05rem;">Página no encontrada</p>
        <a routerLink="/" class="btn btn-light btn-lg px-4 elevation-1">Volver al inicio</a>
      </div>
    </div>
  `,
})
export class NotFoundPageComponent {}
