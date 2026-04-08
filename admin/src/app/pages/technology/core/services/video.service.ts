import { Inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';

import { environment } from '../../../../../environments/environment';
import { 
  baseFilter,
  TableService, 
  TableResponseModel, 
  ITableState, 
  ApiModel
} from '../../../../shared';

import { AlertService } from '../../../../core';
import { Videos } from '../models/videos.model';

@Injectable({
  providedIn: 'root'
})
export class VideoService extends TableService<Videos> {

  // API URL has to be overrided
  override API_URL = `${environment.apiUrl}/admin/technology`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Videos>> {
    return this.http.get<Videos[]>(`${this.API_URL}/getVideos`).pipe(
      map((response: Videos[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Videos> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  } 

  override update(video: Videos): Observable<ApiModel> {
    const url = `${this.API_URL}/updateVideo/${video.id}`;
    return this.http.put<ApiModel>(url, video).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),
      catchError(err => {
        console.error('UPDATE ITEM', err);
        return of({ type: 'error', message: 'Update failed' } as ApiModel);
      }),        
    );    

  }

  override create(video: Videos): Observable<ApiModel> {
    const url = `${this.API_URL}/addVideo`; 
    return this.http.post<ApiModel>(url, video).pipe(  
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),
      catchError(err => {
        console.error('CREATE ITEM', err);
       return of({ type: 'error', message: 'Add failed' } as ApiModel);
      }),      
    );
  }    

  getVideoById(id: number): Observable<ApiModel> {
    return this.http.get<ApiModel>(`${this.API_URL}/getVideoById/${id}`).pipe(
      tap((response) => {
        return response;
      }),
      catchError((err) => {
        console.error('err', err);
        return of({ id: undefined });
      }),      
    );
  }    

  deleteVideo(id: number): Observable<ApiModel> {
    const url = `${this.API_URL}/deleteVideo/${id}`;
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
  
  sortable(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortableVideos';    
    return this.http.put<ApiModel>(url, item).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
        console.log(response)
      }),
      catchError((err) => {
        console.error('err', err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),      
    );
  }     
}
