import { computed, inject, Injectable, signal } from '@angular/core';
import { LoginUser } from '../interfaces/login-user';
import { HttpClient } from '@angular/common/http';
import { NewUser, User } from '../interfaces/user';
import { catchError, map, Observable, of, switchMap, tap } from 'rxjs';
import { environment } from '../../environments/environment';


@Injectable({
  providedIn: 'root',
})
export class Auth {
  private http = inject(HttpClient);
  private readonly url = environment.apiUrl
  private _currentUser = signal<User | null>(null);
  currentUser = this._currentUser.asReadonly();
  isConnected = computed(() => this.currentUser() !== null);

  constructor() {
    this.checkInitialAuth();
  }

  private checkInitialAuth(): void {
    this.profile().subscribe({
      error: () => this._currentUser.set(null),
    });
  }

  login(loginUser: LoginUser) {
    return this.http
      .post<{
        token: string;
      }>(`${this.url}/login`, loginUser, {
        withCredentials: true,
      })
      .pipe(
      switchMap(() => this.profile())
    );
  }

  register(registerUser: NewUser) {
    return this.http
      .post<{
        user: User;
        token: string;
      }>(`${this.url}/register`, registerUser, {
        withCredentials: true,
      })
      .pipe(
      switchMap(() => this.profile())
    );
  }

  profile() {
    return this.http
      .get<User>(`${this.url}/profile`, {
        withCredentials: true,
      })
      .pipe(
        tap((user) => {
          this._currentUser.set(user);
        }),
      );
  }

  checkAuth() {
    return this.profile().pipe(
      map(() => true),
      catchError(() => of(false))
    );
  }

  profileByUser(id: number) {
    return this.http.get<User>(`${this.url}/profile/${id}`, {
      withCredentials: true,
    });
  }

  logout(): Observable<any> {
    return this.http.post<any>(this.url + `/logout`, {}, { withCredentials: true }).pipe(
      tap(() => {
        this._currentUser.set(null);
      }),
    );
  }

  isLoggedIn(): boolean {
    return this._currentUser() !== null;
  }
}
