import { Component, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { LoginUser } from '../../../interfaces/login-user';
import { email, form, required, submit, FormField } from '@angular/forms/signals';
import { Auth } from '../../../services/auth';

type Alert = 'success' | 'warning' | 'danger';

@Component({
  selector: 'app-login',
  imports: [FormField, RouterLink],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private authService = inject(Auth);
  private router = inject(Router);

  alert = signal<Alert | null>(null);
  messageAlert = signal<string | null>(null);
  submitted = signal(false);

  showAlert(message: string, type: Alert, timeout = 3000) {
    this.messageAlert.set(message);
    this.alert.set(type);

    if (timeout) {
      setTimeout(() => this.closeAlert(), timeout);
    }
  }

  closeAlert() {
    this.messageAlert.set(null);
    this.alert.set(null);
  }

  loginModel = signal<LoginUser>({
    username: '',
    password: '',
  });

  loginForm = form(this.loginModel, (fieldPath) => {
    required(fieldPath.username, { message: "L'email est requis" });
    email(fieldPath.username, { message: 'Entrez un adresse email valide' });
    required(fieldPath.password, { message: 'Le mot de passe est requis' });
  });

  onSubmit(event: Event) {
    event.preventDefault();
    this.submitted.set(true)
    if (this.loginForm().invalid()) {
      return;
    }

    submit(this.loginForm, async () => {
      const credentials = this.loginModel();
      this.authService.login(credentials).subscribe({
        next: () => {
          this.showAlert('Connexion Réussie', 'success', 3000);
          setTimeout(() => {
            this.router.navigate(['/catalogue']);
          }, 4000);
        },
        error: (err) => {
          this.showAlert('Email ou mot de passe incorrect', 'danger');
        },
      });
    });
  }
}
