import { Component, inject, signal } from '@angular/core';
import { NewUser, User } from '../../../interfaces/user';
import { Auth } from '../../../services/auth';
import { Router, RouterLink } from '@angular/router';
import { email, form, minLength, required, submit, FormField } from '@angular/forms/signals';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';

type Alert = 'success' | 'warning' | 'danger';

@Component({
  selector: 'app-registration',
  standalone: true,
  imports: [FormField, RouterLink],
  templateUrl: './registration.html',
  styleUrl: './registration.css',
})
export class Registration {
  private authService = inject(Auth);
  private router = inject(Router);

  alert = signal<Alert | null>(null);
  messageAlert = signal<string | null>(null);
  submitted = signal(false);
  registerModel = signal<NewUser>({
    lastname: '',
    firstname: '',
    pseudo: '',
    email: '',
    password: '',
  });

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

  registerForm = form(this.registerModel, (fieldPath) => {
    required(fieldPath.lastname, { message: 'Entrez votre nom' });
    required(fieldPath.firstname, { message: 'Entrez votre prenom' });
    required(fieldPath.pseudo, { message: 'Entrez votre pseudo' });
    required(fieldPath.email, { message: 'Entrezvotre adresse email' });
    email(fieldPath.email, { message: 'Veuillez entrez une adresse email valide' });
    required(fieldPath.password, { message: 'Entrez un mot de passe' });
    minLength(fieldPath.password, 8, { message: 'Votre mot de passe doit minimum 8 caractère' });
  });

  onSubmit(event: Event) {
    event.preventDefault();
    this.submitted.set(true);

    if (this.registerForm().invalid()) return;
    submit(this.registerForm, async () => {
      const credentials = this.registerModel();
      this.authService.register(credentials).subscribe({
        next: () => {
          this.showAlert('Connexion Réussie', 'success', 3000);
          setTimeout(() => {
            this.router.navigate(['/catalogue']);
          }, 4000);
        },
        error: (err) => {
          console.log('❌ erreur reçue:', err);
          console.log('status:', err.status);
          console.log('body:', err.error);

          if (err.status === 409) {
            this.showAlert(err.error.message, 'danger', 4000);
            return;
          }
        },
      });
    });
  }
}
