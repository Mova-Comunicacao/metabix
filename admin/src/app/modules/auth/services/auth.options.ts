import { HttpRequest } from "@angular/common/http";
import { InjectionToken } from "@angular/core";

import { AuthToken, AuthTokenClass } from "./token/token";

export interface AuthOptions {
    forms?: any;
}


//export const AUTH_OPTIONS = new InjectionToken<AuthOptions>('Admin Auth Options');
//export const AUTH_USER_OPTIONS = new InjectionToken<AuthOptions>('Admin User Auth Options');
//export const AUTH_STRATEGIES = new InjectionToken<NbAuthStrategies>('Admin Auth Strategies');
export const AUTH_TOKENS = new InjectionToken<AuthTokenClass<AuthToken>[]>('Admin Auth Tokens');
export const AUTH_INTERCEPTOR_HEADER = new InjectionToken<string>('Admin Simple Interceptor Header');
export const AUTH_TOKEN_INTERCEPTOR_FILTER =
       new InjectionToken<(req: HttpRequest<any>) => boolean>('Admin Interceptor Filter');