import { Inject, Injectable, Injector, Optional } from "@angular/core";
import { EMPTY, Observable, catchError, switchMap, tap, throwError } from "rxjs";

import { HttpRequest, HttpHandler, HttpEvent, HttpInterceptor, HttpErrorResponse, HttpResponse } from "@angular/common/http";

import { 
    AUTH_INTERCEPTOR_HEADER, 
} from "./auth.options";

import { AuthToken } from "./token/token";
import { AuthService } from "./auth.service";

export function NoOpInterceptorFilter(req: HttpRequest<any>): boolean {
  return false;
}

@Injectable()
export class HttpsRequestInterceptor implements HttpInterceptor {
    private headerName: string;
    constructor(
        private injector: Injector,
        @Optional() @Inject(AUTH_INTERCEPTOR_HEADER) headerName?: string,
    ) { 
      this.headerName = headerName || 'Authorization';
    }

    intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
      return this.authService.getToken().pipe(
        switchMap((token: AuthToken | null) => {
          if (token?.token) {
            const setHeaders: Record<string, string> = {};

            if (token?.token) {
              const JWT = `Bearer ${token.token}`;
              setHeaders[this.headerName] = JWT;
            }

            // NÃO setar Content-Type se for FormData
            const isFormData = req.body instanceof FormData;
            if (!isFormData && req.body != null) {
              setHeaders['Content-Type'] = 'application/json';
            }

            const clonedReq = req.clone({ setHeaders });

            return next.handle(req.clone({ setHeaders })).pipe(
              tap({
                next: (event) => {
                  if (event instanceof HttpResponse) {
                    //console.log('SUCCESS:', event.body);
                  }  
                }              
              })
            );
          }
          // Sem token: deixa seguir e trata 401
          return next.handle(req).pipe(
            catchError((err: HttpErrorResponse) => {
              const msg = err?.error?.alert?.message || err.message || 'Erro inesperado.';
              const title = err?.error?.alert?.title || 'unexpected_error';
              const fields = err?.error?.alert?.fields || null;              
              console.error('ERROR:', err)

              if (err?.status === 401) {
                return this.handle401(err);
              }              

              if (err.error?.message) {
                console.warn('Backend error message:', err.error.message);
              }              

              return throwError(() => err);
            })
          );
        }),
      );
    }  

    /**
     * 
     * @param error 
     * @returns 
     */
    handle401(error: HttpErrorResponse) {
      const authResHeader = error.headers.get('WWW-Authenticate') || '';
      if (/is expired/i.test(authResHeader)) {
        this.authService.logout();
      } else {
        // opcional: navegar para rota de falha
      }
      return EMPTY;
    }  

    protected get authService(): AuthService {
      return this.injector.get(AuthService);
    }    
}

@Injectable()
export class WithCredentialsInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    return next.handle(req.clone({ withCredentials: true }));
  }
}