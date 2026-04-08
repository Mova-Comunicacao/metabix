import { Injectable } from "@angular/core";
import { BehaviorSubject, finalize, map, Observable } from "rxjs";

import { AuthHTTPService } from "./auth-http";
import { StaffModel } from '../models/user.model';

import { AlertService } from "../../../core";

@Injectable({
  providedIn: "root",
})
export class PasswordService {  
    isLoadingSubject = new BehaviorSubject<boolean>(false);
    isLoading$: Observable<boolean>;

    constructor(
        private authHttpService: AuthHTTPService,
        private alert: AlertService,
    ) {
        this.isLoadingSubject = new BehaviorSubject<boolean>(false);
        this.isLoading$ = this.isLoadingSubject.asObservable();
    }

    changePassword(admin: StaffModel, password: any): Observable<any> {
        this.isLoadingSubject.next(true);
        return this.authHttpService
        .changePassword(admin, password)
        .pipe(finalize(() => this.isLoadingSubject.next(false)));
    }   

    forgotPassword(email: string): Observable<any> {
        this.isLoadingSubject.next(true);
        return this.authHttpService
        .forgotPassword(email).pipe(
            map((response: any) => {
                if(response) {
                    this.alert.fire(response.type, '', response.message);
                } 
                return response;               
            }),
            finalize(() => this.isLoadingSubject.next(false))
        );
    }  

    newPassword(data: any): Observable<any> {
        this.isLoadingSubject.next(true);
        return this.authHttpService
        .newPassword(data).pipe(
            map((response: any) => {
                if(response) {
                    this.alert.fire(response.type, '', response.message);
                } 
                return response;
            }),
            finalize(() => this.isLoadingSubject.next(false))
        );
    }    
}