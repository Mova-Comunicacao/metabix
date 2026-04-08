import { Inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

import { TableService } from './table.service';
import { AlertService } from '../../../core/services';

@Injectable({
  providedIn: 'root'
})
export class ApiExtendedService extends TableService<any> {
  constructor(@Inject(HttpClient) http: HttpClient, @Inject(AlertService) alert: AlertService) {
    super(http, alert);
  }
}
