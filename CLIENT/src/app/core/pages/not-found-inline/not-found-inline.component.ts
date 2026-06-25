import { Component } from '@angular/core';

@Component({
  standalone: true,
  template: `
    <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
      <div class="display-1 fw-bold text-gray-400 mb-2" style="font-size: 5rem;">404</div>
      <p class="fs-5 fw-medium text-gray-600 mb-1">Página no encontrada</p>
      <p class="text-gray-400">La ruta a la que intentas acceder no existe.</p>
    </div>
  `,
})
export class NotFoundInlineComponent {}
