import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';

// Translate
import { LocalizeRouterModule } from '@gilsdav/ngx-translate-router';
import { TranslateModule } from '@ngx-translate/core';

// Pipes
import { 
  TruncatetextPipe,
  DateLocalePipe,
  SafePipe
} from './pipes';


// Pagination

@NgModule({
  declarations: [ 
    // Pipes
    TruncatetextPipe,
    DateLocalePipe,
    SafePipe,       
  ],
  imports: [
    CommonModule,
    // Translate
    TranslateModule,
    LocalizeRouterModule, 
    InlineSVGModule,  
    // Plugins        
  ],
  exports: [
    // Pipes
    TruncatetextPipe,
    DateLocalePipe,
    SafePipe,   
    // Translate
    TranslateModule,
    LocalizeRouterModule,          
  ],
})
export class SharedModule { }
