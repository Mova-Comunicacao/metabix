import { Injectable, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';

import { 
  TableService, 
  ITableState, 
  TableResponseModel, 
  baseFilter, 
  ApiModel
} from '../../../shared';
import { AlertService } from '../../../core';

import { Social } from '../models/social.model';

@Injectable({
  providedIn: 'root'
})
export class SocialService extends TableService<Social> {

  override API_URL = `${environment.apiUrl}/admin/teams`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Social>> {
    return this.http.get<Social[]>(`${this.API_URL}/getSocial`).pipe(
      map((response: Social[]) => {
        const filteredResult = baseFilter(response.filter(el => el.teamid === tableState.entityId), tableState);
        const result: TableResponseModel<Social> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }  

  override getItemById(id: number): Observable<Social> {
    const url = `${this.API_URL}/getSocial/${id}`;
    return this.http.get<Social>(url).pipe(
      map((response: Social) => {
        return response;
      }),
    );
  }  

  addSocial(item: Social): Observable<ApiModel> {
    const url = `${this.API_URL}/addSocial`; 
    return this.http.post<ApiModel>(url, item).pipe(  
      tap((response) => {
        if (response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),
      catchError(err => {
        this.alert?.toast(err.error?.alert?.type ?? 'error', err.error?.alert?.message ?? 'unexpected error');
        console.error('CREATE SOCIAL', err);
        return of({ id: undefined } as ApiModel);
      }),
    );
  }  

  updateSocial(social: Social): Observable<ApiModel> {
    const url = `${this.API_URL}/updateSocial/${social.id}`;
    return this.http.put<ApiModel>(url, social).pipe(
      tap((response) => {
        if (response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),
      catchError(err => {
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);        
        console.error('UPDATE SOCIAL', social, err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),
    );
  }  

  sortableSocial(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortableSocial';    
    return this.http.post<ApiModel>(url, item).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),
      catchError((err) => {
        console.error('err', err);
        return of({ id: undefined } as ApiModel );
      }),       
    );
  }    

  deleteSocial(id: number): Observable<ApiModel> {
    const url = `${this.API_URL}/deleteSocial/${id}`;
    return this.http.delete<ApiModel>(url).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),
      catchError((err) => {
        console.error('err', err);
        return of({});
      }),      
    );
  }    

}
