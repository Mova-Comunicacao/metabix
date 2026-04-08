import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
// Paginator
import { NgPagination } from './components/paginator/ng-pagination/ng-pagination.component';
import { PaginatorComponent } from './components/paginator/paginator.component';
import { SortIconComponent } from './components/sort-icon/sort-icon.component';
// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
@NgModule({
  declarations: [PaginatorComponent, NgPagination, SortIconComponent],
  imports: [CommonModule, FormsModule, InlineSVGModule ],
  exports: [PaginatorComponent, NgPagination, SortIconComponent],
})
export class CRUDTableModule { }
