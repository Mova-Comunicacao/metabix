import { Injectable } from "@angular/core";
import { Observable, of } from "rxjs";

import { environment } from '../../../../environments/environment'
import { AuthModel } from "../models/auth.model";

@Injectable({
    providedIn: 'root',
})
export class JwtAuthStrategy {
    private authLocalStorageToken = `${environment.appVersion}-${environment.AUTH_KEY}`;

    setToken(auth: AuthModel): void {
        localStorage.setItem(this.authLocalStorageToken, JSON.stringify(auth));
    }   
    
    removeToken(): void {
        return localStorage.removeItem(this.authLocalStorageToken);
    } 

    getCurrentToken(): Observable<AuthModel | any> {
        const token = this.getToken();
        if (token) {    
            //const encodedPayload = token.split(".")[1];
            //const payload = token; //window.atob(encodedPayload);
            return JSON.parse(token);
        } else {
            return of(undefined);
        }
    }    
    
    getToken() {
        return localStorage.getItem(this.authLocalStorageToken);
    }    
}