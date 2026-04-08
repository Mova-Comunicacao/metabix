import { Component, HostBinding } from '@angular/core';
import { Subscription } from 'rxjs';

import { NgbCalendar, NgbDate } from '@ng-bootstrap/ng-bootstrap';
import { OverviewService } from '../../../core';

@Component({
  selector: 'app-widget-dropdown',
  templateUrl: './widget-dropdown.component.html'
})
export class WidgetDropdownComponent {
  @HostBinding('class') class =
  'menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold w-250px pb-2';

  start_date: NgbDate | any;
  end_date: NgbDate | any;

  active: number;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private calendar: NgbCalendar, 
    // Services
    private verviewService: OverviewService    
  ) {}
    
  onLastDaysDateChange(day: number) {
    this.start_date = this.calendar.getPrev(this.calendar.getToday(), 'd', day);
    this.end_date = this.calendar.getToday();  

    this.loadDateRange();
    this.active = day;
  }   

  loadDateRange() {
    const sb = this.verviewService.getDateRange(this.start_date, this.end_date).pipe(     
    ).subscribe();
    this.subscriptions.push(sb);     
  }  
    
}
