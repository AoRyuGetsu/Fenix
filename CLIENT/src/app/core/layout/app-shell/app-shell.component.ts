import { Component, inject } from '@angular/core';
import { DomSanitizer, SafeStyle } from '@angular/platform-browser';
import { RouterOutlet } from '@angular/router';
import { SidebarComponent } from '../sidebar/sidebar.component';
import { ConfigService } from '../../services/config.service';

@Component({
  selector: 'app-app-shell',
  imports: [RouterOutlet, SidebarComponent],
  templateUrl: './app-shell.component.html',
})
export class AppShellComponent {
  private configService = inject(ConfigService);
  private sanitizer = inject(DomSanitizer);

  bgStyle: SafeStyle;

  constructor() {
    const url = this.configService.backgroundUrl;
    this.bgStyle = url
      ? this.sanitizer.bypassSecurityTrustStyle(
          `linear-gradient(180deg, rgba(244,246,248,0.85) 0%, rgba(244,246,248,1) 100%), url(${url}) top center / 100% auto no-repeat`,
        )
      : '';
  }
}
