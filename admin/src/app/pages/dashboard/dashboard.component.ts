import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { catchError, of, Subscription } from 'rxjs';

import { NgbDateStruct, NgbCalendar, NgbDateParserFormatter, NgbDate } from '@ng-bootstrap/ng-bootstrap';

import { DashboardService } from './services';
import { DateRange } from './models';

const now = new Date();

const equals = (one: NgbDateStruct, two: NgbDateStruct) =>
  one && two && two.year === one.year && two.month === one.month && two.day === one.day;

const before = (one: NgbDateStruct, two: NgbDateStruct) =>
  !one || !two ? false : one.year === two.year ? one.month === two.month ? one.day === two.day
  ? false : one.day < two.day : one.month < two.month : one.year < two.year;

const after = (one: NgbDateStruct, two: NgbDateStruct) =>
  !one || !two ? false : one.year === two.year ? one.month === two.month ? one.day === two.day
  ? false : one.day > two.day : one.month > two.month : one.year > two.year;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss'],
})
export class DashboardComponent implements OnInit, OnDestroy {
  hovered_date: NgbDate | any = null;
  start_date: NgbDate | any;
  end_date: NgbDate | any;

  date!: DateRange | null;
  activeId = 7;

  error: any;

  today = new Date();
  max_date: any;

  private subscriptions: Subscription[] = [];

  constructor(
    private cdr: ChangeDetectorRef,
    private calendar: NgbCalendar, 
    public formatter: NgbDateParserFormatter,
    // Services
    private dashboardService: DashboardService
  ) {
    this.start_date = calendar.getPrev(calendar.getToday(), 'd', 7);
    this.end_date = calendar.getPrev(calendar.getToday(), 'd', 1);   
  }

  ngOnInit(): void {
    this.loadDateRange();
    this.max_date = this.calendar.getToday();
  }

  loadDateRange() {
    const sb = this.dashboardService.getDateRange(this.start_date, this.end_date).pipe(
      catchError((err) => {
        this.error = err;
        console.error('err', err);
        return of(undefined);
      }),       
    ).subscribe(res => {
      this.date = res as DateRange;
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sb);     
  }

  onDateSelection(date: NgbDate) {
    if (!this.start_date && !this.end_date) {
      this.start_date = date;
      this.end_date = null;
    } else if (this.start_date && !this.end_date && after(date, this.start_date)) {
      this.end_date = date;
    } else {
      this.end_date = null;
      this.start_date = date;
    } 
  }

  onLastDaysDateChange(day: number) {
    this.start_date = this.calendar.getPrev(this.calendar.getToday(), 'd', day);
    this.end_date = this.calendar.getToday();  

    this.setActiveTab(day);
  }  

  setActiveTab(tabId: number) {
    this.activeId = tabId;
  }

  isActive(tabId: number) {
    if (tabId !== this.activeId) {
      return '';
    }

    return 'active';
  }   

	isHovered(date: NgbDate) {
		return (
			this.start_date && !this.end_date && this.hovered_date && date.after(this.start_date) && date.before(this.hovered_date)
		)
	}

	isInside(date: NgbDate) {
    return this.end_date && date.after(this.start_date) && date.before(this.end_date);
	}

	isRange(date: NgbDate) {      
		return (
			date.equals(this.start_date) ||
			(this.end_date && date.equals(this.end_date)) ||
			this.isInside(date) ||
			this.isHovered(date)      
    );
	}  

	validateInput(currentValue: NgbDate | null, input: string): NgbDate | null {
		const parsed = this.formatter.parse(input);
		return parsed && this.calendar.isValid(NgbDate.from(parsed)) ? NgbDate.from(parsed) : currentValue;
	}

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  } 
}
