import { Injectable } from '@angular/core';
import { urlBase64Decode } from '../helpers';

@Injectable({
    providedIn: 'root',
})
export abstract class AuthToken {

  abstract token: string;
  abstract refreshToken: string;
  abstract expiresIn?: Date;

  abstract getValue(): string;
  abstract isValid(): boolean;
  // the strategy name used to acquire this token (needed for refreshing token)
  abstract toString(): string;
  abstract getCreatedAt(): Date;

  getName(): string {
    return (this.constructor as AuthTokenClass).NAME;
  }

  setAuth(token: AuthToken) {
    this.token = token.token;
    this.refreshToken = token.refreshToken;
    this.expiresIn = token.expiresIn;
  }
  
}

/**
 * Wrapper for simple (text) token
 */
export class AuthSimpleToken extends AuthToken {

  static NAME = 'auth:simple:token';

  constructor(
    override readonly token: any,
    override readonly refreshToken: string,
    override expiresIn: Date
  ) {
    super();
    this.expiresIn = this.prepareCreatedAt(expiresIn);
  }

  protected prepareCreatedAt(date: Date) {
    return date ? date : new Date();
  }

  /**
   * Returns the token's creation date
   * @returns {Date}
   */
  getCreatedAt(): Date {
    return this.expiresIn;
  }

  /**
   * Returns the token value
   * @returns string
   */
  getValue(): string {
    return this.token;
  }

  /**
   * Is non empty and valid
   * @returns {boolean}
   */
  isValid(): boolean {
    return !!this.getValue();
  }

  /**
   * Validate value and convert to string, if value is not valid return empty string
   * @returns {string}
   */
  toString(): string {
    return this.token ? this.token : '';
  }  
}

/*
export abstract class AuthToken {

    protected payload: any = null;
  
    abstract getValue(): string;
    abstract isValid(): boolean;
    // the strategy name used to acquire this token (needed for refreshing token)
    abstract getrefreshToken(): string;
    abstract getexpiresIn(): Date;
    abstract toString(): string;
  
    getName(): string {
      return (this.constructor as AuthTokenClass).NAME;
    }
  
    getPayload(): any {
      return this.payload;
    }
}
*/

export interface AuthTokenClass<T = AuthToken | null> {
    NAME: string;
    new (raw: any, strategyName: string, expDate?: Date): T;
}

export function AuthCreateToken<T extends AuthToken>(tokenClass: AuthTokenClass<T>,
    token: any,
    refreshToken: string,
    expiresIn?: Date) {
    return new tokenClass(token, refreshToken, expiresIn);
}