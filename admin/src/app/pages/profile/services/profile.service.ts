import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, of } from 'rxjs';
import { map, catchError, finalize, tap } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';

import { StaffModel } from '../../../modules/auth';
import { AlertService } from '../../../core';
import { ApiModel } from '../../../shared';

@Injectable({
  providedIn: 'root'
})
export class ProfileService {

  // Public fields
  public _isLoading$ = new BehaviorSubject<boolean>(false);
  public _errorMessage = new BehaviorSubject<string>('');

  // Getters
  get isLoading$() {
    return this._isLoading$.asObservable();
  }
  get errorMessage$() {
    return this._errorMessage.asObservable();
  }  

  protected http: HttpClient;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}/admin/profile`;
  constructor(http: HttpClient, private alert: AlertService) {
    this.http = http;      
  }

  getAdminById(id: number): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    
    return this.http.get<ApiModel>(`${this.API_URL}/get/${id}`).pipe(
      tap((admin) => {
        return admin;
      }),
      catchError((err) => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('GET ITEM BY IT', id, err);
        return of({ id: undefined });
      }),      
      finalize(() => this._isLoading$.next(false))
    );
  } 

  update(staff: StaffModel): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');

    const url = `${this.API_URL}/update/${staff.staffid}`;
    return this.http.put<ApiModel>(url, staff).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type ?? '', response.alert?.message ?? '');
        }
      }),
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('UPDATE ITEM', staff, err);
        return of({ id: null, type: 'error', message: 'unexpected error' } as ApiModel)
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }  
  
  uploadAvatar(staff: StaffModel, file: FormData): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    

    return this.http.post<ApiModel>(`${this.API_URL}/uploadAvatar/${staff.staffid}/`, file).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),      
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'bad_request',
          message: err?.message || 'Upload failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        return of({ id: null, type: 'error', message: 'unexpected error' } as ApiModel);
      }),
       finalize(() => this._isLoading$.next(false))
    );
  }  

  deleteAvatar(staff: StaffModel): Observable<any> {
    this._isLoading$.next(true);
    this._errorMessage.next('');    

    const url = `${this.API_URL}/deleteAvatar/${staff.staffid}`;
    return this.http.delete<ApiModel>(url).pipe(
      tap((response) => {
        if (response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),       
      catchError(err => {
        this._errorMessage.next(err);
        console.error('DELETE AVATAR', staff.staffid, err);
        return of({id: undefined});
      }),
       finalize(() => this._isLoading$.next(false))
    );
  }   

  changePassword(admin: StaffModel, data: { currentPassword: string; password: string }): Observable<any> {
    this._isLoading$.next(true);
    this._errorMessage.next('');

    const url = `${this.API_URL}/change_password/${admin.staffid}`;

    return this.http.post<ApiModel>(url, data).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),      
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Failed to update password.',
        };
        this.alert?.toast(alert.type, alert.message);        
        console.error('UPDATE PASSWORD', err);
        return of({ type: 'error', message: 'unexpected_error' } as ApiModel);
      }),
    );
  }    

}
