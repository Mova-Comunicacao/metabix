import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, Subscription, of } from 'rxjs';
import { finalize, tap, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

import { 
  Languages, 
} from '../models';


@Injectable({
  providedIn: 'root'
})
export class LanguageService implements OnDestroy {
  private readonly STORAGE_KEY = 'app_lang';
  
  // private fields
  private _currentLanguage$ = new BehaviorSubject<string | null>(null);
  private _languages$ = new BehaviorSubject<Languages[]>([]);
  private _isLoading$ = new BehaviorSubject<boolean>(false);

  // Public fields
  
  private _subscriptions: Subscription[] = [];

  // Getters
  get isLoading$() {
    return this._isLoading$.asObservable();
  }
  get language$() {
    return this._languages$.asObservable();
  } 
  get currentLanguage$() {
    return this._currentLanguage$.asObservable();
  } 


  get currentLanguageValue(): string | null {
    return this._currentLanguage$.value;
  }

  get subscriptions() {
    return this._subscriptions;
  }  

  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}/languages`;
  constructor(http: HttpClient) {
    this.http = http;        
    
    const saved = localStorage.getItem(this.STORAGE_KEY);
    this._currentLanguage$.next(saved ?? null);    
  }

  loadLanguages(): Observable<Languages[]> {
    this._isLoading$.next(true);

    return this.http.get<Languages[]>(`${this.API_URL}`).pipe(
      tap((response) => {
        this._languages$.next(response || []);

        if (!this._currentLanguage$.value) {
          // default vindo da API; senão pega o primeiro; senão 'english'
          const fallback = response?.find(l => l.isDefault)?.code || response?.[0]?.code ||
            'english';
          this.setLanguage(fallback);
        }
      }),
      catchError(err => {
        console.error('[Language] fetchLanguages error', err);
        this._languages$.next([]);
        return of([]);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }  

  setLanguage(lang: string) {
    this._currentLanguage$.next(lang);
    if (lang) localStorage.setItem(this.STORAGE_KEY, lang);
    else localStorage.removeItem(this.STORAGE_KEY);
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
