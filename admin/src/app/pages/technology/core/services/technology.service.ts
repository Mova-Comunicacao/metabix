import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, Subscription } from 'rxjs';
import { map, finalize, tap, catchError } from 'rxjs/operators';

import { environment } from '../../../../../environments/environment';

import { ApiModel } from '../../../../shared/crud-table';
import { AlertService } from '../../../../core';

import { Technology } from '../models/technology.model';

@Injectable({
  providedIn: 'root'
})
export abstract class TechnologyService implements OnDestroy  {

  private _errorMessage = new BehaviorSubject<string>('');

  // Public fields
  public _items$ = new BehaviorSubject<any>(undefined);
  public _isLoading$ = new BehaviorSubject<boolean>(false);
  public _subscriptions: Subscription[] = [];


  // Getters
  get items$() {
    return this._items$.asObservable();
  }  
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
  API_URL = `${environment.apiUrl}/admin/technology`;
  constructor(http: HttpClient, private alert: AlertService) {
    this.http = http;
  }

  getTechnology(): Observable<Technology> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    

    return this.http.get<Technology>(`${this.API_URL}`).pipe(
      tap((response: Technology) => {
        if(response) {
          this._items$.next(response);
        }
        return response;
      }),     
      finalize(() => this._isLoading$.next(false))
    );
  } 

  update(business: Technology): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');     

    const url = `${this.API_URL}/update`;
    return this.http.put<ApiModel>(url, business).pipe(
      tap((response) => {
        if(response) {
          this.alert.toast(response.alert?.type, response.alert?.message);
        }
        return response;
      }),  
      catchError(err => {
        console.error('UPDATE TECH', err);
        return of({ type: 'error', message: 'Update failed' } as ApiModel);
      }),    
      finalize(() => this._isLoading$.next(false))   
    ); 
  }      

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }   
}
