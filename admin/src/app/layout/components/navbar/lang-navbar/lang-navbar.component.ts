import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Subscription, debounceTime, distinctUntilChanged, map } from 'rxjs';


import { Languages, LanguageService, Settings, SettingsService } from '../../../../core';

@Component({
  selector: 'app-lang-navbar',
  templateUrl: './lang-navbar.component.html'
})
export class LangNavbarComponent implements OnInit, OnDestroy {
  @Input() toggleBtnClass = '';

  settings: Settings | any = {
    active_language: ''
  };
    
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
    private settingService: SettingsService, 
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