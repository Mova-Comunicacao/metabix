import { Injectable, Inject, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, map, tap } from 'rxjs/operators';
import { Observable, of } from 'rxjs';

import { environment } from '../../../../environments/environment';

import { 
  TableService, 
  ITableState, 
  baseFilter,
  TableResponseModel,
  ApiModel, 
} from '../../../shared';

import { AlertService } from '../../../core';
import { Slide } from '../models/';

@Injectable({
  providedIn: 'root'
})
export class SlideService extends TableService<Slide> {

  override API_URL = `${environment.apiUrl}/admin/slides`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Slide>> {
    return this.http.get<Slide[]>(`${this.API_URL}/getAll`).pipe(
      map((response: Slide[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Slide> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }   

  override getItemById(id: number): Observable<Slide> {
    const url = `${this.API_URL}/getItemById/${id}`;
    return this.http.get<Slide>(url).pipe(
      map((response: Slide) => {
        return response;
      }),
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
