import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, Subscription } from 'rxjs';
import { map } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';
import { 
  AlertService,
} from '../../../core';

import { Permissions } from '../models/permissions.model';

@Injectable({
  providedIn: 'root'
})
export class PermissionService implements OnDestroy {

  // Public fields
  public _items$ = new BehaviorSubject<Permissions[]>([]);
  // Private fields
  private _subscriptions: Subscription[] = [];

  // Getters
  get items$() {
    return this._items$.asObservable();
  }
  get subscriptions() {
    return this._subscriptions;
  }  
      
  API_URL = `${environment.apiUrl}/admin/staff`;

  constructor(
    private http: HttpClient, 
  ) { }

  // READ
  getPermissions(id: number): Observable<Permissions[]> {
    return this.http.get<Permissions[]>(`${this.API_URL}/get_staff_permissions/${id}`).pipe(
      map((response: Permissions[]) => {
        if(response) {
            this._items$.next(response);
        }
        return response;
      })
    );
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }    
}