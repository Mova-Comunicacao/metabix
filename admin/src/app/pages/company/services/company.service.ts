import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, Subscription } from 'rxjs';
import { map, catchError, finalize, tap } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';

import { AlertService } from '../../../core';

import { Company, Picture } from '../models';
import { ApiModel } from '../../../shared';

@Injectable({
  providedIn: 'root'
})
export abstract class CompanyService implements OnDestroy  {

  // Public fields
  public _items$ = new BehaviorSubject<any>(undefined);
  public _isLoading$ = new BehaviorSubject<boolean>(false);
  public _subscriptions: Subscription[] = [];

  private _errorMessage = new BehaviorSubject<string>('');

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
  API_URL = `${environment.apiUrl}/admin/company`;
  constructor(http: HttpClient, private alert: AlertService) {
    this.http = http;
  }

  getCompany(): Observable<Company> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    

    return this.http.get<Company>(`${this.API_URL}/company`).pipe(
      tap((response: Company) => {
        if (response) {
          this._items$.next(response);
        }
        return response;
      }),     
      finalize(() => this._isLoading$.next(false))
    );
  } 

  update(company: Company): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    
      
    const url = `${this.API_URL}/update`;
    return this.http.put<ApiModel>(url, company).pipe(
      tap(response => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
        return response;
      }),  
      catchError(err => {
        console.error('UPDATE COMPANY', err);
        return of({ type: 'error', message: 'Update failed' } as ApiModel);
      }),          
      finalize(() => this._isLoading$.next(false))   
    ); 
  }   

  getPictures(): Observable<Picture[]> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    

    return this.http.get<Picture[]>(`${this.API_URL}/getPictures`).pipe(
      tap((pictures: Picture[]) => {
        return pictures;
      }),   
      finalize(() => this._isLoading$.next(false))
    );
  }  
  
  uploadPicture(file: FormData): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next(''); 

    return this.http.post<ApiModel>(`${this.API_URL}/uploadPicture/`, file).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),     
      catchError(err => {
        this._isLoading$.next(true);
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'bad_request',
          message: err?.message || 'Upload failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        return of({ id: null, type: 'error', message: 'unexpected error' } as ApiModel);
      }),      
      finalize(() => {this._isLoading$.next(false)})      
    );
  }   

  deletePicture(id: number): Observable<ApiModel> {
    this._isLoading$.next(true);
    const url = `${this.API_URL}/deletePicture/${id}`;
    return this.http.delete<ApiModel>(url).pipe(
      tap((response: ApiModel) => {
        if(response) {
          this.alert.toast(response.alert?.type, response.alert?.message);
        }
        return response;
      }),
      catchError((err) => {
        console.error('err', err);
        return of({ type: 'error', message: 'Delete failed' } as ApiModel);
      }),      
      finalize(() => {this._isLoading$.next(false)}) 
    );
  }   
  
  sortable(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortable';    
    return this.http.put<ApiModel>(url, item).pipe(
      tap((response: any) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }        
      }),
      catchError((err) => {
        console.error('err', err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),      
    );
  }     

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }   
}
