import { ChangeDetectionStrategy, Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationCancel, NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { registerLocaleData } from '@angular/common';
import { filter, startWith, Subscription } from 'rxjs';

// 3rd-Party plugins variables
import { TranslateModule } from '@ngx-translate/core';

import localePt from '@angular/common/locales/pt';
import localeEn from '@angular/common/locales/en';
import localeEs from '@angular/common/locales/es';
// local
registerLocaleData(localePt, 'pt');
registerLocaleData(localeEn, 'en');
registerLocaleData(localeEs, 'es');

import { ThemeModeService } from './layout/components/switcher/theme-mode.service';
import { ApiExtendedService } from './shared';

@Component({
  selector: 'body[root]',
  standalone: true,
  imports: [
    RouterOutlet,
    TranslateModule
  ],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AppComponent implements OnInit, OnDestroy {
  title = 'admin';

  private unsubscribe: Subscription[] = [];

  constructor(
    private router: Router,
    // Services
    private readonly ApiExtended: ApiExtendedService,
    private readonly mode: ThemeModeService,
  ) { }  

  ngOnInit() {
    this.mode.init();
    const routerSubscription = this.router.events.pipe(
      filter(e => e instanceof NavigationEnd || e instanceof NavigationCancel),
      startWith(null)
    )
    .subscribe(() => {
      // Trick the Router into believing it's last link wasn't previously loaded
      //this.router.navigated = false;
      // clear filtration paginations and others
      this.ApiExtended.setDefaults();        
      // hide splash screen
      //this.splashService.hide();
      // scroll to top on every route change
      if (typeof document !== 'undefined') {
        window.scroll(0, 0);
        // to display back the body content
        setTimeout(() => {
          document.body.classList.add('page-loaded');
          //document.body.scrollTop = 0; // || window.scrollTo(0, 0);
        }, 100);     
      }     
    }); 
    this.unsubscribe.push(routerSubscription);  
  }  

  ngOnDestroy() {
    this.unsubscribe.forEach((routerSubscription) => routerSubscription.unsubscribe());
  }

  routerOutletActivation(active: boolean) {
    //console.log('routerOutletActivation', active);
  }    

}
