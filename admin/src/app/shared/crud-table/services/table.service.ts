import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';

import { PaginatorState } from '../models/paginator.model';
import { ITableState, TableResponseModel } from '../models/table.model';
import { ApiModel } from '../models/base.model';
import { SortState } from '../models/sort.model';
import { GroupingState } from '../models/grouping.model';
// Alert
import { AlertService } from '../../../core/services';

const DEFAULT_STATE: ITableState = {
  filter: {},
  paginator: new PaginatorState(),
  grouping: new GroupingState(),
  sorting: new SortState(),
  entityId: 0,
  search_string: '',
  language: '',
};

export abstract class TableService<T> {
  // Private fields
  private _items$ = new BehaviorSubject<T[]>([]);
  private _isLoading$ = new BehaviorSubject<boolean>(false);
  private _isFirstLoading$ = new BehaviorSubject<boolean>(true);
  private _tableState$ = new BehaviorSubject<ITableState>(DEFAULT_STATE);
  private _errorMessage = new BehaviorSubject<string>('');
  private _subscriptions: Subscription[] = [];

  // Getters
  get items$() {
    return this._items$.asObservable();
  }
  get isLoading$() {
    return this._isLoading$.asObservable();
  }
  get isFirstLoading$() {
    return this._isFirstLoading$.asObservable();
  }
  get errorMessage$() {
    return this._errorMessage.asObservable();
  }
  get subscriptions() {
    return this._subscriptions;
  }
  // State getters
  get paginator() {
    return this._tableState$.value.paginator;
  }
  get filter() {
    return this._tableState$.value.filter;
  }
  get sorting() {
    return this._tableState$.value.sorting;
  }
  get search_string() {
    return this._tableState$.value.search_string;
  }
  get grouping() {
    return this._tableState$.value.grouping;
  }

  protected http: HttpClient;
  protected alert?: AlertService;
  // API URL has to be overrided
  API_URL = `${environment.apiUrl}`;
  constructor(http: HttpClient, alert: AlertService) {
    this.http = http;
    this.alert = alert;
  }

  // CREATE
  // server should return the object with ID
  create(item: ApiModel): Observable<ApiModel> {
    const url = `${this.API_URL}/create`; 
    this._isLoading$.next(true);
    this._errorMessage.next('');
    return this.http.post<ApiModel>(url, item).pipe(  
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type ?? '', response.alert?.message ?? '');
        }
      }),
      catchError(err => {
        this._errorMessage.next(err);
        this.alert?.toast(err.error?.alert?.type ?? 'error', err.error?.alert?.message ?? 'unexpected error');
        console.error('CREATE ITEM', err);
        return of({ id: undefined } as ApiModel);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }

  // READ (Returning filtered list of entities)
  find(tableState: ITableState): Observable<TableResponseModel<T>> {
    const url = this.API_URL + '/getAll';
    this._errorMessage.next('');
    return this.http.post<TableResponseModel<T>>(url, tableState).pipe(
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('FIND ITEMS', err);
        return of({ items: [], total: 0 });
      })
    );
  }

  getItemById(id: number): Observable<any> {
    this._isLoading$.next(true);
    this._errorMessage.next('');
    const url = `${this.API_URL}/getItemById/${id}`;
    return this.http.get<ApiModel>(url).pipe(
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('GET ITEM BY IT', id, err);
        return of({ id: undefined });
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }

  // UPDATE
  update(item: ApiModel): Observable<any> {
    const url = `${this.API_URL}/update/${item.id}`;
    this._isLoading$.next(true);
    this._errorMessage.next('');
    return this.http.post<ApiModel>(url, item).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type ?? '', response.alert?.message ?? '');
        }
      }),
      catchError((err) => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('UPDATE ITEM', item, err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }

  // UPDATE Status
  updateStatusForItems(ids: number[], active: number): Observable<ApiModel> {
    //this._isLoading$.next(true);
    this._errorMessage.next('');
    const body = { ids, active };
    const url = this.API_URL + '/updateStatus';
    return this.http.post<ApiModel>(url, body).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),      
      catchError(err => {
        this._isLoading$.next(false);
        this._errorMessage.next(err);
        console.error('UPDATE STATUS FOR SELECTED ITEMS', ids, active, err);
        return of({type: 'error', message: 'unexpected error'} as ApiModel);
      }),
      finalize(() => this._isLoading$.next(false))
    )
  }


  // UPDATE Archived
  updateArchivedForItems(ids: number[], archived: any): Observable<any> {
    //this._isLoading$.next(true);
    this._errorMessage.next('');
    
    const body = { ids, archived };
    const url = this.API_URL + '/updateArchived';
    return this.http.post(url, body).pipe(
      catchError(err => {
        this._isLoading$.next(false);
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('UPDATE ARCHIVE FOR SELECTED ITEMS', ids, archived, err);
        return of([]);
      }),
      finalize(() => this._isLoading$.next(false))
    )
  }  

  // DELETE
  delete(id: number): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');
    const url = `${this.API_URL}/delete/${id}`;
    const body = { id };
    return this.http.post<ApiModel>(url, body).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),       
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('DELETE ITEM', id, err);
        return of({ id: null, type: 'error', message: 'unexpected error' } as ApiModel);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }

  // delete list of items
  deleteItems(ids: number[] = []): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');
    const url = this.API_URL + '/deleteItems';
    const body = { ids };
    return this.http.post<ApiModel>(url, body).pipe(
      tap((response) => {
        if(response) {
          this.alert?.toast(response.alert?.type, response.alert?.message);
        }
      }),      
      catchError(err => {
        this._errorMessage.next(err);
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'validation_error',
          message: err?.message || 'Unexpected failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('DELETE SELECTED ITEMS', ids, err);
        return of({ id: null, type: 'error', message: 'unexpected error' } as ApiModel);
      }),
      finalize(() => this._isLoading$.next(false))
    );
  }
   
  // UPLOAD Images
  uploadPicture(data: any): Observable<ApiModel> {
    this._isLoading$.next(true);
    this._errorMessage.next('');  
      
    const url = this.API_URL + '/uploadPicture';
    return this.http.post<ApiModel>(url, data).pipe(
      tap((response) => {
        if(response) {
          if (response?.alert) this.alert?.toast(response.alert?.type, response.alert?.message ?? '');
        }
      }),
      catchError((err) => {
        this._isLoading$.next(false);
        this._errorMessage.next(err);       
        const alert = err?.error?.alert ?? {
          type: 'error',
          title: 'bad_request',
          message: err?.message || 'Upload failed.',
        };
        this.alert?.toast(alert.type, alert.message);
        console.error('UPLOAD IMAGES', err);
        return of({ type: 'error', message: 'unexpected error' } as ApiModel);
      }),  
      finalize(() => this._isLoading$.next(false))     
    );
  } 

  public fetch() {
    this._isLoading$.next(true);
    this._errorMessage.next('');
    const request = this.find(this._tableState$.value)
      .pipe(
        tap((res: TableResponseModel<T>) => {
          this._items$.next(res.items);
          this.patchStateWithoutFetch({
            paginator: this._tableState$.value.paginator.recalculatePaginator(
              res.total
            ),
          });
        }),
        catchError((err) => {
          this._errorMessage.next(err);
          return of({
            items: [],
            total: 0
          });
        }),
        finalize(() => {
          this._isLoading$.next(false);
          const itemIds = this._items$.value.map((el: T) => {
            const item = (el as unknown) as ApiModel;
            return item.id;
          });
          this.patchStateWithoutFetch({
            grouping: this._tableState$.value.grouping.clearRows(itemIds),
          });
        })
      )
      .subscribe();
    this._subscriptions.push(request);
  }

  public setDefaults() {
    this.patchStateWithoutFetch({ filter: {} });
    this.patchStateWithoutFetch({ sorting: new SortState() });
    this.patchStateWithoutFetch({ grouping: new GroupingState() });
    this.patchStateWithoutFetch({ language: '' });
    this.patchStateWithoutFetch({ search_string: '' });
    this.patchStateWithoutFetch({
      paginator: new PaginatorState()
    });
    this._tableState$.next(DEFAULT_STATE);
    this._isFirstLoading$.next(true);
    this._isLoading$.next(true);
    this._errorMessage.next('');
  }

  // Base Methods
  public patchState(patch: Partial<ITableState>) {
    this.patchStateWithoutFetch(patch);
    this.fetch();
  }

  public patchStateWithoutFetch(patch: Partial<ITableState>) {
    const newState = Object.assign(this._tableState$.value, patch);
    this._tableState$.next(newState);
  }
  
}
