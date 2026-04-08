import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

import { environment } from '../../../../../environments/environment';

import { StaffModel } from '../../models/user.model';
import { AuthModel } from '../../models/auth.model';

const API_STAFF_URL = `${environment.apiUrl}/auth`;

@Injectable({
  providedIn: 'root',
})
export class AuthHTTPService {
  constructor(private http: HttpClient) {}

  // public methods
  login(credentials: any): Observable<any> {
    return this.http.post<AuthModel>(`${API_STAFF_URL}/login`, credentials);
  }

  // Your server should check email => If email exists send link to the user and return true | If email doesn't exist return false
  forgotPassword(email: string): Observable<boolean> {
    return this.http.post<boolean>(`${API_STAFF_URL}/forgotPassword`, {
      email,
    });
  }

  newPassword(data: any): Observable<boolean> {
    return this.http.post<boolean>(`${API_STAFF_URL}/newPassword`, data);
  }     

  changePassword(admin: StaffModel, password: any): Observable<boolean> {
    return this.http.post<any>(`${API_STAFF_URL}/changePassword/${admin.staffid}`, {
      password,
    });
  }    

  getMe(): Observable<StaffModel> {
    return this.http.get<StaffModel>(`${API_STAFF_URL}/me`);
  }

  logout(): Observable<any> {
    return this.http.get<any>(`${API_STAFF_URL}/logout`);
  }  
}
