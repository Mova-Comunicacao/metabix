import { Injectable, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, map, tap} from 'rxjs/operators';
import { Observable, of } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { 
  TableService, 
  ITableState, 
  TableResponseModel, 
  baseFilter,
  ApiModel, 
} from '../../../shared';
import { AlertService } from '../../../core';

import { Customers } from '../models/customers.model';

@Injectable({
  providedIn: 'root'
})
export class CustomerService extends TableService<Customers>  {
  
  override API_URL = `${environment.apiUrl}/admin/customers`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Customers>> {
    return this.http.get<Customers[]>(`${this.API_URL}/getAll`).pipe(
      map((response: Customers[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Customers> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }  

  override getItemById(id: number): Observable<Customers> {
    const url = `${this.API_URL}/getItemById/${id}`;
    return this.http.get<Customers>(url).pipe(
      map((response: Customers) => {
        return response;
      }),
    );
  }      
  
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
