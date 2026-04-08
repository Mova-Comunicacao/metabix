import { Component, Input, OnInit } from '@angular/core';
import { Observable } from 'rxjs';

import { ThemeModeService, ThemeModeType } from './theme-mode.service';

@Component({
  selector: 'app-switcher',
  templateUrl: './switcher.component.html',
})
export class SwitcherComponent implements OnInit {
  @Input() toggleBtnClass = '';
  @Input() toggleBtnIconClass = 'svg-icon-2';
  @Input() menuPlacement = 'bottom-end';
  @Input() menuTrigger = "{default: 'click', lg: 'hover'}";

  mode$: Observable<ThemeModeType>;
  menuMode$: Observable<ThemeModeType>;

  constructor(private modeService: ThemeModeService) {}

  ngOnInit(): void {
    this.mode$ = this.modeService.mode.asObservable();
    this.menuMode$ = this.modeService.menuMode.asObservable();
  }

  switchMode(_mode: ThemeModeType): void {
    this.modeService.switchMode(_mode);
  }
}
