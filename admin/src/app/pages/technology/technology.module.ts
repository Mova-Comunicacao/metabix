import { NgModule } from '@angular/core';
import { CommonModule, registerLocaleData } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import localePT from '@angular/common/locales/pt';
registerLocaleData(localePT);
import localeEN from '@angular/common/locales/en';
registerLocaleData(localeEN);
import localeES from '@angular/common/locales/es';
registerLocaleData(localeES);

// 3rd-Party plugins variables
import { QuillModule } from 'ngx-quill';
import { InlineSVGModule } from 'ng-inline-svg-2';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { NgbModalModule, NgbTooltipModule, NgbDropdownModule, NgbDatepickerModule } from '@ng-bootstrap/ng-bootstrap';
import { NgApexchartsModule } from 'ng-apexcharts';

import { OverviewComponent } from './overview/overview.component';
import { ContentComponent } from './content/content.component';
import { ItemsComponent } from './items/items.component';

// Components
// Overview
import { WidgetDateComponent } from './overview/components/widget-date/widget-date.component';
import { WidgetDropdownComponent } from './overview/components/widget-dropdow/widget-dropdown.component';
// Content
import { TechnologyImagesComponent } from './content/components/pictures/technology-images.component';
import { UploadImageComponent } from './content/components/pictures/upload-image/upload-image.component';
import { DeleteImageComponent } from './content/components/pictures/delete-image/delete-image.component';

import { TechonolgyVideosComponent } from './content/components/videos/technology-videos.component';
import { EditVideoComponent } from './content/components/videos/edit-video/edit-video.component';
import { DeleteVideoComponent } from './content/components/videos/delete-video/delete-video.component';

// Items
import { EditItemsComponent } from './items/components/edit-items/edit-items.component';
import { DeleteItemsComponent } from './items/components/delete-items/delete-items.component';
import { UploadImageItemsComponent } from './items/components/upload-image/upload-image-items.component';

import { TechnologyRoutingModule } from './technology-routing.module';
import { TechnologyComponent } from './technology.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    TechnologyComponent,
    OverviewComponent,
    ContentComponent,
    // Widget
    WidgetDateComponent,
    WidgetDropdownComponent,
    // Items
    ItemsComponent,
    EditItemsComponent,
    DeleteItemsComponent,
    UploadImageItemsComponent,
    // Pictures
    TechnologyImagesComponent,
    UploadImageComponent,
    DeleteImageComponent,
    // Videos
    TechonolgyVideosComponent,
    EditVideoComponent,
    DeleteVideoComponent,
  ],
  imports: [
    CommonModule,
    TechnologyRoutingModule,
    FormsModule, 
    ReactiveFormsModule,
    InlineSVGModule,
    DragDropModule,
    NgbModalModule, 
    NgbTooltipModule,
    NgbDropdownModule,
    NgbDatepickerModule,
    QuillModule.forRoot(),
    NgApexchartsModule,
    KeeniconModule,
    CRUDTableModule,
    SharedModule     
  ]
})
export class TechnologyModule { }
