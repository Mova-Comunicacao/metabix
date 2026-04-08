// Angular
import { Pipe, PipeTransform } from '@angular/core';
import { formatDate } from '@angular/common';
import { TranslateService } from '@ngx-translate/core';

@Pipe({ 
    name: 'datelocale', 
    pure: false 
})
export class DateLocalePipe implements PipeTransform {

    constructor(private translate: TranslateService) { }

    /**
     * 
     * @param value string
     * @param format date
     * @returns 
     */
    transform(value: Date | string | number, format = "mediumDate"): string {
        const locale = this.translate.currentLang || 'en';
        return formatDate(value, format, locale);
    }    
}