import { Component, computed, inject, signal } from '@angular/core';
import { ReactiveFormsModule, FormGroup, FormControl, Validators } from '@angular/forms';
import { AuthFacade } from '../../facades/auth.facade';
import { ConfigService } from '../../../services/config.service';

@Component({
  selector: 'app-login-page',
  imports: [ReactiveFormsModule],
  templateUrl: './login-page.component.html',
})
export class LoginPageComponent {
  private facade = inject(AuthFacade);
  private configService = inject(ConfigService);

  private showPassword = signal(false);

  vm = computed(() => ({
    ...this.facade.vm(),
    showPassword: this.showPassword(),
    loginLogoUrl: this.configService.loginLogoUrl,
  }));

  togglePassword(): void {
    this.showPassword.update(v => !v);
  }

  form = new FormGroup({
    email: new FormControl('', [Validators.required, Validators.email]),
    password: new FormControl('', [Validators.required]),
  });

  onSubmit(): void {
    if (this.form.invalid) return;
    const { email, password } = this.form.getRawValue();
    this.facade.login(email!, password!);
  }
}
