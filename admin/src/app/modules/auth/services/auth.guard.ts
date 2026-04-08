import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Router, RouterStateSnapshot, UrlTree } from '@angular/router';
import { map, Observable } from 'rxjs';

import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';
import { AuthService } from './auth.service';

@Injectable({ providedIn: 'root' })
export class AuthGuard  {
  constructor(
    private authService: AuthService,
    private localize: LocalizeRouterService,
    private router: Router
  ) {}

  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<boolean | UrlTree> {
    return this.authService.isAuthenticated().pipe(
      map(authenticated => {
        if (authenticated) return true;

        // traduz o path (sem prefixo de língua) e anexa redirectTo
        const loginPath = this.localize.translateRoute('/auth/login') as string; // ex.: '/pt/auth/login'
        const url = `${loginPath}?redirectTo=${encodeURIComponent(state.url)}`;
        return this.router.parseUrl(url);
      }),
    );
  }

}
