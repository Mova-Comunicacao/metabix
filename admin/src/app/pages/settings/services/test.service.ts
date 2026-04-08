import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, catchError, finalize, map, Observable, of, Subscription } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { AlertService } from '../../../core';

@Injectable({
  providedIn: 'root'
})
export class TestService implements OnDestroy {
  
  // Public fields
  public _isSending$ = new BehaviorSubject<boolean>(false);

  // Getters
  get isSending$() {
    return this._isSending$.asObservable();
  }

  API_URL = `${environment.apiUrl}`;

  private subscriptions: Subscription[] = [];

  constructor(
    private http: HttpClient,
    private alert: AlertService
  ) { }

  sent_smtp_test_email(data: any): Observable<any> {
    this._isSending$.next(true);
    const url = `${this.API_URL}/admin/emails/sent_smtp_test_email`;  
    console.log();  
    return this.http.post<any>(url, data).pipe(
      map((response) => {
        this.alert.fire(response.type, response.title, response.message);
        return response;
      }),
      catchError((err) => {
        this.alert.toast('error', err.statusText);
        //console.error('err', data, err);
        return of(undefined);
      }),  
      finalize(() => this._isSending$.next(false))      
    );
  }  
  
  sent_whatsapp_test_template(data: any): Observable<any> {
    const url = `${this.API_URL}/whatsapp/sendTemplate`;  
    console.log();  
    return this.http.post<any>(url, data).pipe(
      map((response) => {
        this.alert.fire(response.type, response.title, response.message);
      })
    );
  }    
  
  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }    

}
