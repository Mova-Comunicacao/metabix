import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, of, Subscription } from 'rxjs';
import { map, catchError, finalize, tap } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';

import { 
  Settings, 
} from '../models';

import { AlertService } from '../../../core';
import { ApiModel } from '../../../shared';

@Injectable({
  providedIn: 'root'
})
export class SettingsService implements OnDestroy {

  // Public fields
  public _settings$ = new BehaviorSubject<any>(undefined);
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
  get settings$() {
    return this._settings$.asObservable();
  } 
  get subscriptions() {
    return this._subscriptions;
  }  

  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}/admin/settings`;
  constructor(http: HttpClient, private alert: AlertService) {
    this.http = http;    

    const subscr = this.getSettings().subscribe();
    this.subscriptions.push(subscr);        
  }

  getSettings(): Observable<Settings> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    
    return this.http.get<Settings>(`${this.API_URL}`).pipe(
      map((response: Settings) => {
        if (response) {
          this._settings$.next(response);
        }   
        return response;
      }),     
      finalize(() => this._isLoading$.next(false))
    );
  }  
  
  update(settings: Settings): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');   

    const url = `${this.API_URL}/update`;
    return this.http.post<ApiModel>(url, settings).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type ?? '', response.alert?.message ?? '');
        }
      }),      
      catchError((err) => {
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);        
        console.error('err', settings, err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),      
      finalize(() => this._isLoading$.next(false))   
    ); 
  } 

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
