import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, Subscription } from 'rxjs';
import { map, catchError, finalize } from 'rxjs/operators';
import { tap } from 'rxjs';

import { environment } from '../../../../environments/environment';

import { Charts, Activity, Client, Contact, ITableState, TableResponseModel, MetricResponse, MetricPoint } from '../models';

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
  start_date: ymd(new Date(today.getTime() - 6*24*3600*1000)),
  end_date: ymd(today)
};

@Injectable({
  providedIn: 'root'
})
export class DashboardService {

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
    //this.currentDateSubject = new BehaviorSubject<DateRange | any>(undefined);
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

  getClients(): Observable<Client[]> {
    this._isLoading$.next(true);
    this._errorMessage.next('');  

    return this.http.get<Client[]>(`${this.API_URL}`).pipe(
      map((response: any) => {     
        return response['clients'];
      }),
      catchError((err) => {
        console.error('err', err);
        return of(undefined);
      }),      
      finalize(() => this._isLoading$.next(false))
    );
  }
  
  getContacts(): Observable<Contact[]> {
    this._isLoading$.next(true);
    this._errorMessage.next('');  

    return this.http.get<Contact[]>(`${this.API_URL}`).pipe(
      map((response: any) => {     
        const result = response['contacts'];
        return result;
      }),
      catchError((err) => {
        console.error('err', err);
        return of(undefined);
      }),      
      finalize(() => this._isLoading$.next(false))
    );
  }  

  getActivity(): Observable<Activity[]> {
    this._isLoading$.next(true);
    this._errorMessage.next('');  
   
    return this.http.get<Activity[]>(`${this.API_URL}`).pipe(
      map((response: any) => { 
        return response['activity'];
      }),
      catchError((err) => {
        console.error('err', err);
        return of(undefined);
      }),      
      finalize(() => this._isLoading$.next(false))
    );
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
  
  getReportData(tableState: ITableState): Observable<TableResponseModel<Charts>>  {   
    this._isLoading$.next(true);
    this._errorMessage.next('');     
    
    const payload = {
      start_date: tableState.start_date ?? null,
      end_date: tableState.end_date ?? null,
      metrics: tableState.metrics || [],
      dimensions: tableState.dimensions || []
    };

    return this.http.post<Charts[] | any>(`${this.API_URL}/getReportData/`, payload, {
      headers: { 'Content-Type': 'application/json' }
    }).pipe(
      map((response: Charts[] | any) => {
        if (response && Array.isArray(response.items) && typeof response.total === 'number') {
          return response as TableResponseModel<Charts>;
        }

        const filteredResult = baseFilter(response as Charts[] ?? [], tableState);
        const result: TableResponseModel<Charts> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;        
      }),
      catchError((err) => {
        const msg = err?.error?.message || err?.message || 'Falha ao obter dados do relatório.';
        console.error('getReportData error', err);
        this._errorMessage.next(msg);
        // Nunca retorne undefined: devolva estrutura vazia
        const empty: TableResponseModel<Charts> = { items: [], total: 0 };
        return of(empty);
      }),      
      finalize(() => this._isLoading$.next(false))
    );
  } 
  
  getRealtimeReportData(tableState: ITableState): Observable<TableResponseModel<Charts>> {   
    this._isLoading$.next(true);
    this._errorMessage.next('');     

    const payload = {
      start_date: tableState.start_date ?? null,
      end_date: tableState.end_date ?? null,
      metrics: tableState.metrics || [],
      dimensions: tableState.dimensions || []
    };

    return this.http.post<Charts[]>(`${this.API_URL}/getRealtimeReportData/`, payload, {
      headers: { 'Content-Type': 'application/json' }
    }).pipe(
      map((response: Charts[] | any) => {
        if (response && Array.isArray(response.items) && typeof response.total === 'number') {
          return response as TableResponseModel<Charts>;
        }

        const filteredResult = baseFilter(response as Charts[] ?? [], tableState);
        const result: TableResponseModel<Charts> = {
          items: filteredResult.items,
          total: filteredResult.total
        };
        return result;        
      }),
      catchError((err) => {
        const msg = err?.error?.message || err?.message || 'Falha ao obter dados do relatório.';
        console.error('getReportData error', err);
        this._errorMessage.next(msg);
        // Nunca retorne undefined: devolva estrutura vazia
        const empty: TableResponseModel<Charts> = { items: [], total: 0 };
        return of(empty);
      }),    
      finalize(() => this._isLoading$.next(false))
    );
  }   
  
  getReportMetricData(tableState: ITableState): Observable<MetricResponse> {
    this._isLoading$.next(true);
    this._errorMessage.next('');

    const payload = {
      start_date: tableState.start_date ?? null,
      end_date: tableState.end_date ?? null,
      metrics: tableState.metrics ?? []
    };

    return this.http.post<any>(`${this.API_URL}/getReportMetricData`, payload, {
      headers: { 'Content-Type': 'application/json' }
    }).pipe(
      map((resp: any): MetricResponse => {
        if (resp && typeof resp.totals === 'number' && Array.isArray(resp.series)) {
          return resp as MetricResponse;
        }      
        
        // { rows: [{name:'activeUsers', value: 10}, ...] }
        if (resp?.rows && Array.isArray(resp.rows)) {
          const series: MetricPoint[] = resp.rows.map((r: any) => ({
            name: r.name ?? r.metric ?? 'total',
            value: Number(r.value ?? r.total ?? 0)
          }));
          const totals = series.reduce((acc, p) => acc + (isNaN(p.value) ? 0 : p.value), 0);
          return { totals, series };
        }
              
        // { metrics: { activeUsers: 123, newUsers: 45 } }
        if (resp?.metrics && typeof resp.metrics === 'object') {
          const series: MetricPoint[] = Object.entries(resp.metrics).map(([k, v]) => ({
            name: k,
            value: Number(v)
          }));
          const totals = series.reduce((acc, p) => acc + (isNaN(p.value) ? 0 : p.value), 0);
          return { totals, series };
        }

        if (typeof resp === 'number') {
        }
        return { totals: resp, series: [{ name: 'total', value: resp }] };

        // fallback seguro
        return { totals: 0, series: [] };
      }),
      catchError((err) => {
        const msg = err?.error?.message || err?.message || 'Falha ao obter métricas.';
        console.error('getReportMetricData error', err);
        this._errorMessage.next(msg);
        return of<MetricResponse>({ totals: 0, series: [] });
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }

}
