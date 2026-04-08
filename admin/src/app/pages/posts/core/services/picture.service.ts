import { Injectable, OnDestroy, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';

import { environment } from '../../../../../environments/environment';

import { 
  TableService, 
  ApiModel,
} from '../../../../shared';
import { AlertService } from '../../../../core';

import { Pictures } from '../models';

@Injectable({
  providedIn: 'root'
})
export class PictureService extends TableService<Pictures> {

  // API URL has to be overrided
  override API_URL = `${environment.apiUrl}/admin/posts`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  override getItemById(id: number): Observable<Pictures[]> {
    const url = `${this.API_URL}/getPictures/${id}`;
    return this.http.get<Pictures[]>(url).pipe(
      tap((response: Pictures[]) => {
        return response;
      }),
      catchError((err) => {
        console.error('err', err);
        return of([] as Pictures[]);
      }),         
    );
  }  
    
  // DELETE
  deletePicture(id: number): Observable<ApiModel> {
    const url = `${this.API_URL}/deletePicture/${id}`;
    return this.http.delete<ApiModel>(url).pipe(
      tap((response) => {
        if (response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }), 
      catchError((err) => {
        this.alert?.toast(err?.type ?? 'error', err?.message ?? '');
        console.error('err', err);
        return of({ id: undefined } as ApiModel );
      }),      
    );
  } 

  getPicturesById(id: number): Observable<Pictures> {
    return this.http.get<Pictures>(`${this.API_URL}/getPicturesById/${id}`).pipe(
      tap((picture: Pictures) => {
        return picture;
      }),      
    );
  }   


  sortable(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortablePictures';    
    return this.http.put<ApiModel>(url, item).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),
      catchError((err) => {
        console.error('err', err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),      
    );
  }    
}
