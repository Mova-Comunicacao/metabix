import { Injectable, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, map, tap } from 'rxjs/operators';
import { Observable, of } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { 
  TableService, 
  ITableState, 
  TableResponseModel, 
  baseFilter, 
  ApiModel
} from '../../../shared';
import { AlertService } from '../../../core';

import { Category } from '../models/category.model';

@Injectable({
  providedIn: 'root'
})
export class CategoriesService extends TableService<Category>  {
  
  override API_URL = `${environment.apiUrl}/admin/categories`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Category>> {
    return this.http.get<Category[]>(`${this.API_URL}/getAll`).pipe(
      map((response: Category[]) => {
        const filteredResult = baseFilter(response, tableState);
        const result: TableResponseModel<Category> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  }  

  override getItemById(id: number): Observable<Category> {
    const url = `${this.API_URL}/getItemById/${id}`;
    return this.http.get<Category>(url).pipe(
      map((response: Category) => {
        return response;
      }),
    );
  }     
  
  deletePicture(id: number): Observable<any> {
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
  
  sortable(data: any[]): Observable<any> {
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
