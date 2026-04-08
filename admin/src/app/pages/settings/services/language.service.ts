import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, of, Subscription } from 'rxjs';
import { map, catchError, finalize } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';

import { 
  Languages, 
} from '../models';


@Injectable({
  providedIn: 'root'
})
export class LanguageService implements OnDestroy {

  // Public fields
  public _language$ = new BehaviorSubject<any>(undefined);
  public _isLoading$ = new BehaviorSubject<boolean>(false);

  // private fields
  private _subscriptions: Subscription[] = [];

  // Getters
  get isLoading$() {
    return this._isLoading$.asObservable();
  }
  get language$() {
    return this._language$.asObservable();
  } 
  get subscriptions() {
    return this._subscriptions;
  }  

  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}/languages`;
  constructor(http: HttpClient) {
    this.http = http;           
  }

  getLanguages(): Observable<Languages> {
    this._isLoading$.next(true);
    return this.http.get<Languages>(`${this.API_URL}`).pipe(
      map((response: Languages) => {
        if (response) {
          this._language$.next(response);
        }   
        return response;
      }),     
      finalize(() => this._isLoading$.next(false))
    );
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
