import { Inject, Injectable, InjectionToken } from '@angular/core';

import { 
    AuthCreateToken, 
    AuthToken, 
    AuthTokenClass 
} from './token';

export interface TokenPack {
    token: string,
    refreshToken: string,
    expiresIn: number,
    value: string,
}

export const AUTH_FALLBACK_TOKEN = new InjectionToken<AuthTokenClass>('Staff Auth Options');
export const AUTH_TOKENS = new InjectionToken<AuthTokenClass<AuthToken>[]>('Staff Auth Tokens');

/**
 * Creates a token parcel which could be stored/restored
 */
@Injectable({
    providedIn: 'root',
})
export class AuthTokenParceler {

    constructor(
       // @Inject(AUTH_FALLBACK_TOKEN) private fallbackClass: AuthTokenClass,
       // @Inject(AUTH_TOKENS) private tokenClasses: AuthTokenClass[]
    ) { }

    wrap(token: AuthToken): string {
        return JSON.stringify({
            token: token.token,
            refreshToken: token.refreshToken,
            expiresIn: token?.expiresIn,
            value: token.toString(),
        });
    }
    
    unwrap(value: string): AuthToken {
        let tokenValue = '';
        let refreshToken = '';
        let expiresIn: Date | any = null;
    
        const tokenPack: TokenPack | any = this.parseTokenPack(value);
        if (tokenPack) {
            tokenValue = tokenPack.token;
            refreshToken = tokenPack.refreshToken;
            expiresIn = new Date(Number(tokenPack.expiresIn));            
        }
        
        return tokenPack;
    }
    
    /*
    
    // TODO: this could be moved to a separate token registry
    protected getClassByName(name: string): AuthTokenClass | undefined {
        return this.tokenClasses.find((tokenClass: AuthTokenClass) => tokenClass.NAME === name);
    }
    */

    
    protected parseTokenPack(value: string): TokenPack | undefined | null {
        try {
          return JSON.parse(value);
        } catch (e) { }
        return null;
    }    
}