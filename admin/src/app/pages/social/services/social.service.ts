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
  ApiModel, 
} from '../../../shared';
import { AlertService } from '../../../core';

import { Social } from '../models';

@Injectable({
  providedIn: 'root'
})
export class SocialService extends TableService<Social> {

  override API_URL = `${environment.apiUrl}/admin/social`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Social>> {
    return this.http.get<Social[]>(`${this.API_URL}/getAll`).pipe(
      map((response: Social[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Social> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }    

  sortable(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortable';    
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

}
