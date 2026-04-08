import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { 
  NgbDropdownModule, 
  NgbTooltipModule,
  NgbModalModule 
} from '@ng-bootstrap/ng-bootstrap';

import { AdminsRoutingModule } from './admins-routing.module';
import { AdminsComponent } from './admins.component';

import { AdminListComponent } from './admin-list/admin-list.component';
import { AdminEditComponent } from './admin-edit/admin-edit.component';
import { PermissionsComponent } from './admin-edit/components/permissions/permissions.component';
import { PasswordComponent } from './admin-edit/components/password/password.component';

import { CreateAdminComponent } from './components/create-admin/create-admin.component';
import { DeleteAdminComponent } from './components/delete-admin/delete-admin.component';

import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    AdminsComponent,
    AdminListComponent,
    AdminEditComponent,
    PermissionsComponent,
    PasswordComponent,
    CreateAdminComponent,
    DeleteAdminComponent,
  ],
  imports: [
    CommonModule,
    AdminsRoutingModule,
    FormsModule, 
    ReactiveFormsModule,
    InlineSVGModule,
    NgbDropdownModule, 
    NgbTooltipModule, 
    NgbModalModule, 
    KeeniconModule,
    CRUDTableModule,
    SharedModule,
  ]
})
export class AdminsModule { }
