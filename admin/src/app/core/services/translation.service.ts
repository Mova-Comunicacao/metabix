// Localization is based on '@ngx-translate/core';
// Please be familiar with official documentations first => https://github.com/ngx-translate/core
import { Injectable } from '@angular/core';

import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';
import { TranslateService } from '@ngx-translate/core'
import { BehaviorSubject, Observable } from 'rxjs';

export interface Locale {
    lang: string;
    data: any;
}

@Injectable({
    providedIn: 'root',
})
export class TranslationService {
    // Private properties
    private langIds: any = [];
    
    // public fields
    currentLanguage$: Observable<string>;
    currentLanguageSubject: BehaviorSubject<string>;

    isTranslationsLoaded = false;

    get currentLanguageValue(): string {
        return this.currentLanguageSubject.value;
    }

    set currentLanguageValue(lang: string) {
        this.currentLanguageSubject.next(lang);
    }

    constructor(
        private localize: LocalizeRouterService,
        private translate: TranslateService,
    ) {
        this.currentLanguageSubject = new BehaviorSubject<string>('');
        this.currentLanguage$ = this.currentLanguageSubject.asObservable();        
        // add new langIds to the list
        this.translate.addLangs(['en', 'es', 'pt']);
        this.currentLanguageSubject.next(this.getSelectedLanguage());

        this.loadLanguage();
    }

    loadLanguage(...args: Locale[]): void {
        const locales = [...args];
    
        locales.forEach((locale) => {
          // use setTranslation() with the third argument set to true
          // to append translations instead of replacing them
          this.translate.setTranslation(locale.lang, locale.data, true);
          this.langIds.push(locale.lang);
        });    
        // add new languages to the list
        this.translate.addLangs(this.langIds);
        this.translate.use(this.getSelectedLanguage()).subscribe({
            next: () => {
                this.isTranslationsLoaded = true;
            },
            error: (error) => {
                console.error('Failed to load translations:', error);
                // You might want to show an error message or fallback
                this.isTranslationsLoaded = true;
            }            
        });
    }      
    
    setLanguage(lang: string) {
        if (lang) {
            this.currentLanguageSubject.next(lang);
            /*
            this.translate.use(lang).subscribe({
                next: () => {
                    this.isTranslationsLoaded = true;
                },
                error: (error) => {
                    console.error('Failed to set translations:', error);
                    // You might want to show an error message or fallback
                    this.isTranslationsLoaded = true;
                }            
            });
            */
            this.localize.changeLanguage(lang, { replaceUrl: true });
        }
    }

    /**
     * Returns selected language
     */
    getSelectedLanguage() {
        return this.localize.parser.currentLang ? this.localize.parser.currentLang : 'en';
    }    
}