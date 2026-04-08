import { Injectable } from '@angular/core';
import { Observable, BehaviorSubject, of as observableOf } from 'rxjs';

import { environment } from '../../../../../environments/environment';

import { AuthTokenParceler } from './token-parceler';
import { AuthToken } from './token';

@Injectable()
export abstract class TokenStorage {
  abstract get(): AuthToken | null;
  abstract set(token: AuthToken | null): void;
  abstract clear(): void;
}

@Injectable({
    providedIn: 'root',
})
export class TokenLocalStorage extends TokenStorage {

    protected storageKey = `${environment.appVersion}-${environment.AUTH_KEY}`;

    constructor(private parceler: AuthTokenParceler) {
        super();
    }

    /**
     * Returns token from localStorage
     * @returns {AuthToken}
     */
    get(): AuthToken | null {
        const raw = localStorage.getItem(this.storageKey);
        if (!raw) return null;
        return this.parceler.unwrap(raw) as AuthToken | null;
    }

    /**
     * Sets token to localStorage
     * @param {AuthToken} token
     */
    set(token: AuthToken | null): void {
        if (!token) return this.clear();
        const raw = this.parceler.wrap(token);
        localStorage.setItem(this.storageKey, raw);
    }

    /**
     * Clears token from localStorage
     */
    clear() {
        localStorage.removeItem(this.storageKey);
    }

    protected parseTokenPack(value: any): any {
        try {
          return JSON.parse(value);
        } catch (e) { }
        return undefined;
    }    

}