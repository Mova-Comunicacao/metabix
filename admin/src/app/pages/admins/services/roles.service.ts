import { Injectable, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, Subscription } from 'rxjs';
import { map } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';

import { Roles } from '../models/roles.model';

@Injectable({
  providedIn: 'root'
})
export class RoleService implements OnDestroy {

  // Public fields
  public _items$ = new BehaviorSubject<Roles[]>([]);
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
  getRoles(): Observable<Roles[]> {
    return this.http.get<Roles[]>(`${this.API_URL}/get_roles/`).pipe(
      map((response: Roles[]) => {
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