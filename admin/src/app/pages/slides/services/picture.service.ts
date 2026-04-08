import { Inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';
import { 
  baseFilter,
  TableService, 
  TableResponseModel, 
  ITableState, 
  ApiModel
} from '../../../shared';

import { AlertService } from '../../../core';
import { Pictures } from '../models';

@Injectable({
  providedIn: 'root'
})
export class PictureService extends TableService<Pictures> {

  // API URL has to be overrided
  override API_URL = `${environment.apiUrl}/admin/slides`;

  constructor(
    @Inject(HttpClient) http: HttpClient, alert: AlertService) {
    super(http, alert);
  }

  // READ
  override find(tableState: ITableState): Observable<TableResponseModel<Pictures>> {
    return this.http.get<Pictures[]>(`${this.API_URL}/getPictures`).pipe(
      map((response: Pictures[]) => {
        const filteredResult = baseFilter(response.filter(el => el.slideid == tableState.entityId), tableState);
        const result: TableResponseModel<Pictures> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;
      })
    );
  } 


  getPicturesById(id: number): Observable<Pictures[]> {
    return this.http.get<Pictures[]>(`${this.API_URL}/getPictures/${id}`).pipe(
      tap((picture: Pictures[]) => {
        return picture;
      }),
      catchError((err) => {
        console.error('err', err);
        return of([]);
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
}
