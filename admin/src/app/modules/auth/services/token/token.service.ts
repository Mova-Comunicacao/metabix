import { afterNextRender, Injectable } from '@angular/core';
import { Observable, BehaviorSubject, of as observableOf } from 'rxjs';
import { filter, shareReplay } from 'rxjs/operators';

import { environment } from '../../../../../environments/environment';

import { TokenStorage } from './token-storage';
import { AuthToken } from './token';


/**
 * Service that allows you to manage authentication token - get, set, clear and also listen to token changes over time.
 */
@Injectable({
    providedIn: 'root',
})
export class TokenService {

    protected storageKey = `${environment.appVersion}-${environment.AUTH_KEY}`;
    protected token$ = new BehaviorSubject<AuthToken | null>(null);

    constructor(
        protected tokenStorage: TokenStorage
    ) {
        afterNextRender(() => {
            this.publishStoredToken();
        });
    }

    /**
     * Publishes token when it changes.
     * @returns {Observable<AuthToken>}
     */
    tokenChange(): Observable<AuthToken> {
        return this.token$
        .pipe(
            filter((t): t is AuthToken => !!t),
            shareReplay(1),
        );
    }

    /**
     * Sets a token into the storage. This method is used by the NbAuthService automatically.
     *
     * @param {AuthToken} token
     * @returns {Observable<any>}
     */
    set(token: AuthToken | null): Observable<null> {
        this.tokenStorage.set(token);
        this.publishStoredToken();
        return observableOf(null);
    }

    /**
     * Returns observable of current token
     * @returns {Observable<AuthToken>}
     */
    get(): Observable<AuthToken | null> {
        return observableOf(this.tokenStorage.get());
    }

    /**
     * Removes the token and published token value
     *
     * @returns {Observable<any>}
     */
    clear(): Observable<null> {
        this.tokenStorage.clear();
        this.publishStoredToken();
        return observableOf(null);
    }
    

    protected publishStoredToken(): void {
        this.token$.next(this.tokenStorage.get());
    }    
}