import { Injectable, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';

import { environment } from '../../../../../environments/environment';

import { 
  TableService, 
  TableResponseModel,
  ITableState,
  baseFilter,
  ApiModel, 
} from '../../../../shared';
import { AlertService } from '../../../../core';

import { Activity, Posts } from '../models';

@Injectable({
  providedIn: 'root'
})
export class PostsService extends TableService<Posts> {

  override API_URL = `${environment.apiUrl}/admin/posts`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState | any): Observable<TableResponseModel<Posts>> {
    const params: any = [];

    const filtrationFields = Object.keys(tableState);
    filtrationFields.forEach((keyName) => {
      params[keyName] = tableState[keyName];
    });

    return this.http.get<Posts[]>(`${this.API_URL}/getAll`, { params }).pipe(
      map((response: Posts[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Posts> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }    
  
  override getItemById(id: number): Observable<Posts> {
    const url = `${this.API_URL}/getItemById/${id}`;
    return this.http.get<Posts>(url).pipe(
      map((response: Posts) => {
        return response;
      }),
    );
  }    

  // Update Feature
  updateFeatureForItem(ids: number[], feature: number): Observable<ApiModel> {
    const body = { ids, feature };
    const url = this.API_URL + '/updateFeature';
    return this.http.put<ApiModel>(url, body).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),       
      catchError(err => {
        console.error('UPDATE FEATURE FOR ITEM', ids, feature, err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),
    )
  }
  
  // Update Launching
  updateLaunching(ids: number[], launching: number): Observable<ApiModel> {
    const body = { ids, launching };
    const url = this.API_URL + '/updateLaunching';
    return this.http.put<ApiModel>(url, body).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),      
      catchError(err => {
        console.error('UPDATE  LAUNCHING FOR ITEM', ids, launching, err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),
    )
  }    

  sortable(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortable';    
    return this.http.put<ApiModel>(url, item).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),
      catchError((err) => {
        console.error('err', err);
        return of({ id: undefined } as ApiModel );
      }),      
    );
  }   

  getActivity(id?: number): Observable<Activity[]> {
    const url = `${this.API_URL}/getActivity/${id}`;
    return this.http.get<Activity[]>(url).pipe(
      map((response: Activity[]) => {
        return response;
      }),
    );
  }    
  
  
}
