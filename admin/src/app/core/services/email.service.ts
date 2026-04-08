import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export abstract class EmailService {

  // Public fields
  public _isLoading$ = new BehaviorSubject<boolean>(false);

  // private fields
  private _errorMessage = new BehaviorSubject<string>('');
  private _subscriptions: Subscription[] = [];

  // Getters
  get isLoading$() {
    return this._isLoading$.asObservable();
  }
  get errorMessage$() {
    return this._errorMessage.asObservable();
  }    
  get subscriptions() {
    return this._subscriptions;
  }  
  
  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}`;
  constructor(http: HttpClient) {
    this.http = http;             
  }  

  send_email(data: any): Observable<any> {
    this._isLoading$.next(true);
    this._errorMessage.next('');      
    const url = `${this.API_URL}/send_email`;  
    console.log();  
    return this.http.post<any>(url, data).pipe(
      catchError((err) => {
        console.error('err', err);
        return of(undefined);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  } 

  sent_smtp_test_email(data: any): Observable<any> {
    this._isLoading$.next(true);
    this._errorMessage.next('');      
    const url = `${this.API_URL}/sent_smtp_test_email`;  
    console.log();  
    return this.http.post<any>(url, data).pipe(
      catchError((err) => {
        console.error('err', err);
        return of(undefined);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  } 

}