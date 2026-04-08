import { APP_INITIALIZER, ApplicationConfig, importProvidersFrom, isDevMode } from '@angular/core';
import { HTTP_INTERCEPTORS, HttpClient, provideHttpClient, withFetch, withInterceptorsFromDi, withNoXsrfProtection } from '@angular/common/http';
import { BrowserModule } from '@angular/platform-browser';
import { provideRouter, RouteReuseStrategy, withDisabledInitialNavigation } from '@angular/router';
import { Location } from '@angular/common';

// 3rd-Party plugins variables
import { CacheMechanism, LocalizeParser, LocalizeRouterSettings, ManualParserLoader, withLocalizeRouter } from '@gilsdav/ngx-translate-router';
import { LocalizeRouterHttpLoader } from '@gilsdav/ngx-translate-router-http-loader';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';

//@ts-ignore - We ignore this because there is no initialize method on the HTMLElement
export function ManualLoaderFactory(translate: TranslateService, location: Location, settings: LocalizeRouterSettings) {
    return new ManualParserLoader(translate, location, settings, ['pt', 'en', 'es'], 'ROUTES.', '!');
}

export function HttpLoaderFactory(translate: TranslateService, location: Location, settings: LocalizeRouterSettings, http: HttpClient) {
  return new LocalizeRouterHttpLoader(translate, location, { ...settings, alwaysSetPrefix: true }, http, `./assets/locales.json`);
}

import { CoreModule, CustomReuseStrategy, createTranslateLoader } from './core';
import { routes } from './app.routes';

import { AuthService, HttpsRequestInterceptor, WithCredentialsInterceptor } from './modules/auth';
import { TokenLocalStorage, TokenStorage } from './modules/auth/services/token/token-storage';
import { AUTH_INTERCEPTOR_HEADER } from './modules/auth/services/auth.options';

import { provideServiceWorker } from '@angular/service-worker';

function appInitializer(authService: AuthService) {
  return () => {
    return new Promise((resolve) => {
      //@ts-ignore
      authService.isAuthenticatedOrRefresh().subscribe().add(resolve);
    });
  };
}

export const appConfig: ApplicationConfig = {
  providers: [
    importProvidersFrom(
      BrowserModule, 
      CoreModule, 
      TranslateModule.forRoot({
        loader: {
          provide: TranslateLoader,
          useFactory: createTranslateLoader,
          deps: [HttpClient]
        }
    })),
    provideRouter(
      routes, 
      withDisabledInitialNavigation(), 
      withLocalizeRouter(routes, {
        parser: {
          provide: LocalizeParser,
          useFactory: HttpLoaderFactory,
          deps: [TranslateService, Location, LocalizeRouterSettings, HttpClient]
        },
        initialNavigation: true,
        cacheMechanism: CacheMechanism.Cookie,
        cookieFormat: '{{value}};{{expires:20}};path=/',
    })),
    provideServiceWorker('ngsw-worker.js', {
      enabled: !isDevMode(),
      registrationStrategy: 'registerWhenStable:30000'
    }),    
    provideHttpClient(
      withFetch(),
      withNoXsrfProtection(), 
      withInterceptorsFromDi()
    ),
    { provide: RouteReuseStrategy, useClass: CustomReuseStrategy },
    { provide: TokenStorage, useClass: TokenLocalStorage },
    { provide: HTTP_INTERCEPTORS, useClass: WithCredentialsInterceptor, multi: true },
    { provide: HTTP_INTERCEPTORS, useClass: HttpsRequestInterceptor, multi: true },
    { provide: AUTH_INTERCEPTOR_HEADER, useValue: 'Authorization' },
    {
      provide: APP_INITIALIZER,
      useFactory: appInitializer,
      deps: [AuthService],
      multi: true,
    },
  ]
};
