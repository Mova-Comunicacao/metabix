import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, Subscription } from 'rxjs';
import { map, catchError, finalize } from 'rxjs/operators';

import { environment } from '../../../../../environments/environment';
import { tap } from 'rxjs';

export interface ITableState {
    filter: {} | any;
    dimensions: any;
    metrics: any;  
    start_date: any;
    end_date: any;
    dimensions_filter: string;
}

export interface TableResponseModel<T> {
    items: T[];
    total: number;
}

export interface Charts {
    x: number;
    y: number;
    flags?: string;
    report?: any,
    dimensions: any;
    metrics: any;
    dimensions_filter?: string;
}

export interface NgbDateStruct { year: number; month: number; day: number; }
export interface DateRange { start_date: string | null; end_date: string | null; }

export function baseFilter(entities: any[], requestObj: any) {
  // Filtration
  const entitiesResult = filterArray(entities, requestObj);

  // Paginator
  // start
  const totalCount = entitiesResult.length;
  //const startPosition = (requestObj.paginator.page - 1) * requestObj.paginator.pageSize;
  //const endPosition = startPosition + requestObj.paginator.pageSize;
  //entitiesResult = entitiesResult.slice(startPosition, endPosition);
  // end

  const responseObj = {
    items: entitiesResult,
    total: totalCount
  };
  return responseObj;
}

export function filterArray(incomingArray: any[], requestObj: any): any[] {
  if (!requestObj || !requestObj.filter) {
    return incomingArray;
  }

  let result: any[] = incomingArray;
  const filtrationFields = Object.keys(requestObj.filter);
  filtrationFields.forEach((keyName: string) => {
       result = result.filter(el => el[keyName] == requestObj.filter[keyName]);
  });
  return result;
}

const today = new Date();
const ymd = (d: Date) => d.toISOString().slice(0,10);

const DEFAULT_STATE: ITableState = {
  filter: {},
  metrics: ['activeUsers'],
  dimensions: ['day'],
  dimensions_filter: 'Empresas',
  start_date: ymd(new Date(today.getTime() - 6*24*3600*1000)),
  end_date: ymd(today)
};

@Injectable({
    providedIn: 'root'
})
export class OverviewService {
    // Private fields
    private _tableState$ = new BehaviorSubject<ITableState>(DEFAULT_STATE);
    private _isLoading$ = new BehaviorSubject<boolean>(false);
    private _errorMessage = new BehaviorSubject<string>('');
    private _subscriptions: Subscription[] = [];

    // public fields
    currentDate$!: Observable<DateRange | null>;
    currentDateSubject = new BehaviorSubject<DateRange | null>(null);


    // Getters
    get isLoading$() {
      return this._isLoading$.asObservable();
    }
    get errorMessage$() {
      return this._errorMessage.asObservable();
    }  
    get subscriptions() {
      return this._subscriptions;
    }

    // State getters
    get filter() {
      return this._tableState$.value.filter;
    }
    // Date
    get start_date() {
      return this._tableState$.value.start_date;
    }
    get end_date() {
      return this._tableState$.value.end_date;
    }  
    get currentDateValue(): DateRange | null {
      return this.currentDateSubject.value;
    }
    set currentDateValue(date: DateRange | null) {
      this.currentDateSubject.next(date);
    }

    // API URL has to be overrided
    API_URL = `${environment.apiUrl}/admin/dashboard`;
    constructor(private http: HttpClient) { 
        this.currentDate$ = this.currentDateSubject.asObservable();
    }    

    // util: formata para YYYY-MM-DD com zero padding
    private toYMD(d?: NgbDateStruct | null): string | null {
      if (!d) return null;
      const y = String(d.year);
      const m = String(d.month).padStart(2, '0');
      const day = String(d.day).padStart(2, '0');
      return `${y}-${m}-${day}`;
    }    

    getDateRange(start?: NgbDateStruct | null, end?: NgbDateStruct | null): Observable<DateRange | null> {
      const startStr = this.toYMD(start);
      const endStr = this.toYMD(end);
    
      this._isLoading$.next(true);
      this._errorMessage.next('');  
      this.currentDateSubject.next(null);  

      // path segments 
      let url = `${this.API_URL}/getDateRange`;
      if (startStr && endStr) url += `/${startStr}/${endStr}`;
      else if (startStr)      url += `/${startStr}`;

      return this.http.get<DateRange>(url).pipe(
        tap((date) => this.currentDateSubject.next(date ?? null)),
        catchError((err) => {
          console.error('getDateRange error', err);
          this._errorMessage.next('Falha ao obter intervalo de datas.');
          return of(null);
        }),
        finalize(() => this._isLoading$.next(false))
      ); 
    }  

    getFilterReportData(tableState: ITableState): Observable<Charts | any> {   
      this._isLoading$.next(true);
      this._errorMessage.next('');     
    
        
      return this.http.post<Charts[]>(`${this.API_URL}/getFilterReportData/`, tableState).pipe(
        map((response: Charts[]) => {
          const filteredResult = baseFilter(response, tableState);
          const result: TableResponseModel<Charts> = {
            items: filteredResult.items,
            total: filteredResult.total
          };
          return result;        
        }),
        catchError((err) => {
          console.error('err', err);
          return of(undefined);
        }),      
        finalize(() => this._isLoading$.next(false))
      );
    }     

    getReportFilterMetricData(tableState: ITableState): Observable<Charts | any> {       
      this._isLoading$.next(true);
      this._errorMessage.next('');     
    
      return this.http.post<Charts>(`${this.API_URL}/getReportFilterMetricData/`, tableState).pipe(
        map((response: Charts) => {
          return response;
        }),
        catchError((err) => {
          console.error('err', err);
          return of(undefined);
        }),      
        finalize(() => this._isLoading$.next(false))
      );     
    }    
}