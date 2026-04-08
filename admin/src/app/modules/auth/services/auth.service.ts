import { Injectable, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, BehaviorSubject, of as observableOf, Subscription, of } from 'rxjs';
import { map, catchError, switchMap, finalize, take, tap } from 'rxjs/operators';

import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';
import { AuthHTTPService } from './auth-http';

import { TokenService } from './token/token.service';
import { AuthToken } from './token/token';

import { StaffModel } from '../models/user.model';
import { AlertService } from "../../../core";

export type UserType = StaffModel | null;

interface ApiAuthResponse {
  type: 'success' | 'error';
  message: string;
  token?: string;               // JWT
  refreshToken?: string;        // plaintext, vem 1x
  expiresIn?: string;           // ISO-8601 (ex.: 2025-09-16T12:34:56Z)
}

@Injectable({
  providedIn: 'root',
})
export class AuthService implements OnDestroy {
  // private fields
  private unsubscribe: Subscription[] = []; // Read more: => https://brianflove.com/2016/12/11/anguar-2-unsubscribe-observables/

  // public fields
  currentUserSubject = new BehaviorSubject<UserType>(null);
  isLoadingSubject = new BehaviorSubject<boolean>(false);

  currentUser$ = this.currentUserSubject.asObservable();
  isLoading$ = this.isLoadingSubject.asObservable();

  get currentUserValue(): UserType {
    return this.currentUserSubject.value;
  }
  set currentUserValue(user: UserType) {
    this.currentUserSubject.next(user);
  }

  constructor(
    private localize: LocalizeRouterService,
    // Protected
    protected authHttpService: AuthHTTPService,
    protected tokenService: TokenService,
    protected alert: AlertService,
    protected router: Router
  ) {}

  // public methods
  login(credentials: any): Observable<boolean> {
    this.isLoadingSubject.next(true);

    return this.authHttpService.login(credentials).pipe(
      switchMap((res: ApiAuthResponse) => {
        if (res.type === 'success' && res?.token) {
          const auth: AuthToken = {
            token: res.token,
            refreshToken: res.refreshToken ?? '',
            // importante: converter ISO -> Date, se seu AuthToken espera Date
            expiresIn: res.expiresIn ? new Date(res.expiresIn) : undefined,
            getValue() { return this.token; },
            isValid() { return !!this.token; },
            toString() { return this.token ?? ''; },
            getCreatedAt() { return this.expiresIn ?? new Date(); },
            setAuth(t: any) { Object.assign(this, t); }
          } as any;

          // salva o token e segue
          return this.tokenService.set(auth).pipe(map(() => true));
        } else {
          this.alert.toast(res?.type ?? 'error', res?.message ?? 'Falha no login');
          return observableOf(false);
        }
      }),
      // se salvou o token, carrega o usuário; senão retorna false
      switchMap(ok => ok ? this.isAuthenticatedOrRefresh() : observableOf(false)),
      catchError(err => {
        console.error('login error', err);
        this.alert.toast('error', 'Erro ao autenticar');
        return observableOf(false);
      }),
      finalize(() => this.isLoadingSubject.next(false)),
    );
  }

  /**
   * Retrieves current authenticated token stored
   * @returns {Observable<null>}
   */
  getToken(): Observable<AuthToken | null> {
    return this.tokenService.get().pipe(take(1));
  }

  /**
   * Returns true if auth token is present in the token storage
   * @returns {Observable<boolean>}
   */
  isAuthenticated(): Observable<boolean> {
    return this.getToken().pipe(
      map((token: AuthToken | null) => !!(token && token.token)),
    );
  }

  /**
   * Returns true if valid auth token is present in the token storage.
   * If not, calls the strategy refreshToken, and returns isAuthenticated() if success, false otherwise
   * @returns {Observable<boolean>}
   */  
  isAuthenticatedOrRefresh(): Observable<boolean> {
    this.isLoadingSubject.next(true);

    return this.getToken().pipe(
      take(1),
      switchMap(token => {
        if (!token?.token) return of(false);
        return this.authHttpService.getMe().pipe(
          tap(user => {
            if (user) this.currentUserSubject.next(user);
          }),
          map(user => !!user),
          catchError(err => {
            // 401 => não autenticado
            this.alert.toast(err?.type ?? 'error', err?.message ?? 'Unauthorized');
            if (err?.status === 401) {
              return this.tokenService.clear().pipe(
                tap(() => {
                  this.currentUserSubject.next(null);
                  // redireciona para login (ajuste a rota se usar LocalizeRouter)
                  const loginPath = this.localize.translateRoute('/auth/login') as string;
                  this.router.navigate([loginPath], {
                    onSameUrlNavigation: 'reload'
                  });                  
                }),
                map(() => err),
              );
            }
            console.error('getMe error', err);
            return of(false);
          }),
        );
      }),
      finalize(() => this.isLoadingSubject.next(false))
    );
  }

  /**
   * Sign outs with the selected strategy
   * Removes token from the token storage
   */  
  logout(): Observable<any> {
    return this.authHttpService.logout().pipe(
      switchMap((result: any) => {
        if (result?.type === 'success') {
          return this.tokenService.clear().pipe(
            tap(() => this.currentUserSubject.next(null)),
            map(() => result),
          );
        }
        return observableOf(result);
      }),
      tap(() => {
        // redireciona para login (ajuste a rota se usar LocalizeRouter)
        const loginPath = this.localize.translateRoute('/auth/login') as string;
        this.router.navigate([loginPath], {
          onSameUrlNavigation: 'reload'
        });
      }),
      catchError(err => {
        console.error('logout error', err);
        return observableOf({ type: 'error', message: 'Erro ao sair' });
      }),
    );
  }

  ngOnDestroy() {
    this.unsubscribe.forEach((sb) => sb.unsubscribe());
  }
}
