import { Injectable, Inject, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, finalize, map } from 'rxjs/operators';
import { Observable, of } from 'rxjs';

import { environment } from '../../../../environments/environment';

import { 
  TableService, 
  ITableState, 
  baseFilter,
  TableResponseModel,
  ApiModel, 
} from '../../../shared';

import { Goals } from '../models';
import { AlertService } from '../../../core'

@Injectable({
  providedIn: 'root'
})
export class GoalService extends TableService<Goals> implements OnDestroy  {
  
  override API_URL = `${environment.apiUrl}/admin/goals`;

  constructor(
    @Inject(HttpClient) http: HttpClient, 
    @Inject(AlertService) alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Goals>> {
    return this.http.get<Goals[]>(`${this.API_URL}/getAll`).pipe(
      map((response: Goals[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Goals> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }  

  sortable(data: any[]): Observable<any> {
    const item = { data };
    const url = this.API_URL + '/sortable';    
    return this.http.post(url, item).pipe(
      map(item => {
        return item;
      }),
      catchError((err) => {
        console.error('err', err);
        return of(undefined);
      }),      
      finalize(() => {})
    );
  }    

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }   
}
