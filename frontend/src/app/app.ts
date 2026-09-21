import { Component, effect, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLinkWithHref, Router, RouterLinkActive } from '@angular/router';
import { Auth } from './services/auth';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLinkWithHref, RouterLinkActive],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  protected readonly title = signal('frontend');

  authService = inject(Auth);
  private router = inject(Router);

  logout() {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/catalogue']);
    });
  }

  isLoginPage(): boolean {
    return this.router.url === '/login' || this.router.url === '/admin' || this.router.url === '/register' || this.router.url === '/login-admin';
  }
}
