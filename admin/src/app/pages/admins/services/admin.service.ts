import { Injectable, Inject, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, catchError, tap, finalize } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';
import { 
  baseFilter,
  TableService, 
  TableResponseModel, 
  ITableState, 
  ApiModel,
} from '../../../shared';

import { AlertService } from '../../../core';
import { Admin } from '../models/admin.model';

@Injectable({
  providedIn: 'root'
})
export class AdminService extends TableService<Admin> {

  override API_URL = `${environment.apiUrl}/admin/staff`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Admin>> {
    return this.http.get<Admin[]>(`${this.API_URL}/getAll`).pipe(
      map((response: Admin[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Admin> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }   

  override getItemById(id: number): Observable<Admin> {
    const url = `${this.API_URL}/getItemById/${id}`;
    return this.http.get<Admin>(url).pipe(
      tap((response: Admin) => {
        return response;
      }),
    );
  }    

  uploadAvatar(staff: Admin, file: FormData): Observable<ApiModel> {
    return this.http.post<ApiModel>(`${this.API_URL}/uploadAvatar/${staff.staffid}/`, file).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),      
      catchError(err => {
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'bad_request',
          message: err?.message || 'Upload failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        return of({ id: null, type: 'error', message: 'unexpected error' } as ApiModel);
      }),
    );
  }  

  deleteAvatar(staff: Admin): Observable<ApiModel> {
    const url = `${this.API_URL}/deleteAvatar/${staff.staffid}`;
    return this.http.delete<ApiModel>(url).pipe(
      tap((response) => {
        if (response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),       
      catchError(err => {
        console.error('DELETE AVATAR', staff.staffid, err);
        return of({id: undefined});
      }),
    );
  }   
  
  changePassword(admin: Admin, data: { currentPassword: string; password: string }): Observable<ApiModel> {
    const url = `${this.API_URL}/change_password/${admin.staffid}`;

    return this.http.post<ApiModel>(url, data).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),      
      catchError(err => {
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
