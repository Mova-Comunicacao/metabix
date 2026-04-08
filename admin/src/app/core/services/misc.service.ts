import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, Subscription } from 'rxjs';
import { map, finalize } from 'rxjs/operators';

import { environment } from '../../../environments/environment';

import { 
  Countries, 
} from '../models';

@Injectable({
  providedIn: 'root'
})
export class MiscService implements OnDestroy {

  // Public fields
  public _countries$ = new BehaviorSubject<Countries[]>([]);
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
  get countries$() {
    return this._countries$.asObservable();
  } 

  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}/admin/misc`;
  constructor(http: HttpClient) {
    this.http = http;    
  }

  getCountries(): Observable<Countries[]> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    
    return this.http.get<Countries[]>(`${this.API_URL}/get_countries`).pipe(
      map((response: Countries[]) => {
        if (response) {
          this._countries$.next(response);
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
