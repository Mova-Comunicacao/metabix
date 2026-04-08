import { Component, HostBinding, Input, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Subscription, debounceTime, delay, distinctUntilChanged, filter, map } from 'rxjs';


import { Languages, LanguageService, Settings, SettingsService, TranslationService } from '../../../../core';
import { AuthService, StaffModel } from '../../../../modules/auth';

@Component({
  selector: 'app-user-navbar',
  templateUrl: './user-navbar.component.html',
})
export class UserNavbarComponent implements OnInit, OnDestroy {
  @HostBinding('class')
  class = `menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px`;
  @HostBinding('attr.data-kt-menu') dataKtMenu = 'true';

  @Input() staff: StaffModel;
  
  settings!: Settings;
  firstSettingState!: Settings;
  
  currentLang: string | null = null;
  languages: any[] = [];

  getLang?: Languages;
  getFlag?: {
    flag: string
  };
  
  formGroup!: FormGroup;
  
  private unsubscribe: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private route: ActivatedRoute,
    // Services
    private translation: TranslationService,
    private settingService: SettingsService, 
    private auth: AuthService,
    // Public
    public languageService: LanguageService,
  ) { }

  ngOnInit(): void {
    this.loadLanguages();
    this.loadSettings();
    
    const sb = this.languageService.currentLanguage$
      .pipe(
        map((lang: any) => this.normalizeLangValue(lang)), // { code, obj }
        distinctUntilChanged((a, b) => a.code === b.code),
      )
      .subscribe(({ code, obj }) => {
        this.currentLang = code || 'english';
        this.getLang = obj ?? this.findLang(this.currentLang);
        this.getFlag = { ...obj, ...pickFlag(code) };

        this.loadForm();
    });
    this.unsubscribe.push(sb);
  }
  
  loadSettings() {
    const sb = this.settingService.settings$.pipe(
    ).subscribe(res => {
      this.settings = res;
    });
    this.unsubscribe.push(sb);       
  }

  loadLanguages() {
    this.currentLang = this.languageService.currentLanguageValue;
    this.languageService.loadLanguages().subscribe(() => {
      if (this.currentLang && !this.getLang) {
        this.getLang = this.findLang(this.currentLang) ?? this.buildFallback(this.currentLang);
      }      
    });
  }   

  setLanguage(lang: string) {
    this.formGroup?.get('active_language')?.setValue(lang)
  }  

  switchLang(lang: string) {
    this.translation.setLanguage(lang); 
    const sb = this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event) => {
      if (event instanceof NavigationEnd) {    
        //this.currentLang  = this.translation.getSelectedLanguage();
      }
    });
    this.unsubscribe.push(sb);    
  }  

  private loadForm(lang?: string) {
    if (!this.formGroup) {
      this.formGroup = this.fb.group({
        active_language: [lang, Validators.required],
      });    

      this.formGroup = this.fb.group({
        active_language: [this.currentLang],
      });    
      this.unsubscribe.push(
        this.formGroup.controls['active_language']?.valueChanges.pipe(
          distinctUntilChanged(),
          debounceTime(200)
        ).subscribe((value: string) => this.onLangFormChange(value))
      );  
    }  else {
      // mantém form em sync se vier novo settings/lang
      this.formGroup.patchValue({ active_language: lang }, { emitEvent: false });
    }
  }    

  private onLangFormChange(lang: string) {
    this.languageService.setLanguage(lang);

    const next = { ...this.settings, active_language: lang };

    this.settingService.update(next).pipe(
    ).subscribe(() => {
      this.router.navigate([],  {
        relativeTo: this.route,
        queryParams: { refresh: new Date().getTime() },
        queryParamsHandling: 'merge',
        //onSameUrlNavigation: 'reload',
        //skipLocationChange: true,
      });      
    });
  } 

  logout() {
    this.auth.logout().pipe(
      delay(500)
    ).subscribe();
  }  


  private normalizeLangValue(lang: Languages): { code: string; obj: Languages | null } {
    // se já é objeto completo, devolve direto
    if (lang && typeof lang === 'object') {
      const code = lang.language ?? lang.code ?? 'english';
      return { code, obj: lang as Languages };
    }
    // se é string/código, procura na lista em cache
    const code = (lang as string) ?? 'english';
    const found = this.findLang(code);
    return { code, obj: found ?? null };
  }

  // busca na lista por language OU code
  private findLang(code: string): Languages | undefined {
    return this.languages.find(l => l?.language === code || l?.code === code);
  }

  // fallback pra não quebrar o template caso não ache
  private buildFallback(code: string): Languages {
    return { language: code, name: code, flag: '', code: code };
  }

  ngOnDestroy() {
    this.unsubscribe.forEach((sb) => sb.unsubscribe());
  }  

}

function pickFlag(lang: string): { flag: string } {
  const key = (lang || '').trim().toLowerCase();
  switch (key) {
    case 'en':
    case 'english':
      return { flag: './assets/flags/united-states.svg' };

    case 'pt':
    case 'portuguese':
      return { flag: './assets/flags/brazil.svg' };

    case 'es':
    case 'spanish':
      return { flag: './assets/flags/spain.svg' };      

    default:
      // fallback opcional
      return { flag: './assets/flags/united-states.svg' };
  }
}