import { ChangeDetectionStrategy, Component, Input, OnInit } from '@angular/core';
import { Observable } from 'rxjs';

import { AuthService, StaffModel } from '../../../modules/auth';
import { TranslationService } from '../../../core';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NavbarComponent implements OnInit {
  @Input() appHeaderDefaulMenuDisplay: boolean;
  @Input() isRtl: boolean;

  itemClass = 'ms-1 ms-lg-3';
  btnClass = 'btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px';
  userAvatarClass = 'symbol-35px symbol-md-40px';
  btnIconClass = 'fs-2 fs-md-1';

  staff$: Observable<StaffModel | null>;

  constructor(
    // Services
    private translation: TranslationService,    
    private auth: AuthService
  ) {
    this.staff$ = this.auth.currentUserSubject.asObservable()
  }

  ngOnInit(): void {
    const lang = this.auth.currentUserValue?.default_language;
    const langMap: Record<string, string> = {
      english: 'en',
      portuguese: 'pt',
      spanish: 'es'
    };
    const langCode = langMap[lang as keyof typeof langMap] ?? '';
    this.switchLang(langCode);
  }  

  switchLang(lang: string) {  
    this.translation.setLanguage(lang);
  }

  getAvatar() {
    if (!this.auth.currentUserValue?.avatar || this.auth.currentUserValue?.avatar === null) {
      return `./assets/media/avatars/blank.png`;
    }
    return `${this.auth.currentUserValue?.avatar}`;
  }
}
