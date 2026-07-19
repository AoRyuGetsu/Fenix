import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class CatalogService {
  private readonly prefix = 'catalogs.';

  getCatalog<T>(key: string): T[] {
    const raw = localStorage.getItem(this.prefix + key);
    if (!raw) return [];
    try { return JSON.parse(raw) as T[]; } catch { return []; }
  }
}
