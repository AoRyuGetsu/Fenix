import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

interface MessageEntry {
  message: string;
  desc: string;
}

@Injectable({ providedIn: 'root' })
export class MessageService {
  private http = inject(HttpClient);
  private messages: Record<string, string> = {};

  get(key: string, params?: Record<string, string | number>): string {
    let msg = this.messages[key];
    if (!msg) return key;
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        msg = msg.replaceAll(`{${k}}`, String(v));
      }
    }
    return msg;
  }

  load(): Promise<void> {
    return firstValueFrom(
      this.http.get<{ features: Record<string, string> }>('/messages/index.json'),
    ).then(async (index) => {
        const files = index?.features ?? {};
        const entries = await Promise.allSettled(
          Object.values(files).map((file) =>
            firstValueFrom(this.http.get<Record<string, MessageEntry>>(`/messages/${file}`)),
          ),
        );
        for (const result of entries) {
          if (result.status !== 'fulfilled' || !result.value) continue;
          for (const [key, value] of Object.entries(result.value)) {
            this.messages[key] = value.message;
          }
        }
      });
  }
}
