import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, Subscription } from 'rxjs';
import { map, finalize } from 'rxjs/operators';

import { environment } from '../../../environments/environment';

import { 
  Settings, 
} from '../models';

@Injectable({
  providedIn: 'root'
})
export class SettingsService implements OnDestroy {

  // Public fields
  settingSubject: BehaviorSubject<Settings>;
  currentSetting$: Observable<Settings>;

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

  get settingsValue(): Settings {
    return this.settingSubject.value;
  }

  set settingsValue(setting: Settings) {
    this.settingSubject.next(setting);
  }

  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}/admin/settings`;
  constructor(http: HttpClient) {
    this.http = http;    

    this.settingSubject = new BehaviorSubject<Settings | any>(undefined);
    this.currentSetting$ = this.settingSubject.asObservable();
        
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
          this.settingSubject.next(response);
        }   
        return response;
      }),     
      finalize(() => this._isLoading$.next(false))
    );
  }  

  update(settings: Settings): Observable<Settings> {
    this._isLoading$.next(true);
    this._errorMessage.next('');      
    const url = `${this.API_URL}/update`;
    console.log();
    return this.http.post(url, settings, ).pipe(
      map((response: any) => {
        return response;
      }),    
      finalize(() => this._isLoading$.next(false))   
    ); 
  }   

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
