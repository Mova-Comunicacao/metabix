import { Injectable, Inject, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, map } from 'rxjs/operators';
import { Observable } from 'rxjs';

import { environment } from '../../../../../environments/environment';
import { 
  TableService, 
  ITableState, 
  TableResponseModel, 
  baseFilter 
} from '../../../../shared';

import { AlertService } from '../../../../core';
import { Category } from '../models/category.model';

@Injectable({
  providedIn: 'root'
})
export class CategoriesService extends TableService<Category> {
  
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
   
}
