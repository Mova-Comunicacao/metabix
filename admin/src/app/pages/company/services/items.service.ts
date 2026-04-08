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

import { Items } from '../models';
import { AlertService } from '../../../core'

@Injectable({
  providedIn: 'root'
})
export class ItemService extends TableService<Items> implements OnDestroy  {
  
  override API_URL = `${environment.apiUrl}/admin/company`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Items>> {
    return this.http.get<Items[]>(`${this.API_URL}/getItems`).pipe(
      map((response: Items[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Items> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }  

  override getItemById(id: number): Observable<Items> {
    const url = `${this.API_URL}/getItems/${id}`;
    return this.http.get<Items>(url).pipe(
      tap((response: Items) => {      
        return response;
      })
    );
  }  

  addItems(item: Items): Observable<ApiModel> {
    const url = `${this.API_URL}/addItems`; 
    return this.http.post<ApiModel>(url, item).pipe(  
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

  updateItems(item: Items): Observable<ApiModel> {
    const url = `${this.API_URL}/updateItems/${item.id}`;
    return this.http.put<ApiModel>(url, item).pipe(
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

  sortableItems(data: any[]): Observable<ApiModel> {
    const item = { data };
    const url = this.API_URL + '/sortableItems';    
    return this.http.post<ApiModel>(url, item).pipe(
      tap((response) => {
        this.alert?.toast(response.alert?.type, response.alert?.message);
      }),
      catchError((err) => {
        console.error('err', err);
        return of({ id: undefined, type: 'error', message: 'Update failed' } as ApiModel );
      }),      
    );
  }    

  deleteItem(id: number): Observable<ApiModel> {
    const url = `${this.API_URL}/deleteItem/${id}`;
    return this.http.delete<ApiModel>(url).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),
      catchError((err) => {
        console.error('err', err);
        return of({ id: undefined, type: 'error', message: 'Update failed' } as ApiModel );;
      }),      
    );
  }    

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }   
}
