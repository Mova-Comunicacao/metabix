import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { NgbDropdownModule, NgbTooltipModule } from '@ng-bootstrap/ng-bootstrap';

import { ProfileRoutingModule } from './profile-routing.module';
import { ProfileComponent } from './profile.component';

import { SettingsComponent } from './components/settings/settings.component';
import { ProfileCardComponent } from './card/profile-card/profile-card.component';
import { PersonalInfoComponent } from './components/settings/personal-info/personal-info.component';
import { AccountInfoComponent } from './components/settings/account-info/account-info.component';
import { ChangePasswordComponent } from './components/settings/change-password/change-password.component';
import { OverviewComponent } from './components/overview/overview.component';

import { DropdownCardComponent } from './dropdown/dropdown-card/dropdown-card.component';

import { 
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    ProfileComponent, 
    ProfileCardComponent, 
    OverviewComponent,
    SettingsComponent,
    PersonalInfoComponent, 
    AccountInfoComponent, 
    ChangePasswordComponent, 
    DropdownCardComponent
  ],
  imports: [
    CommonModule,
    ProfileRoutingModule,
    FormsModule, 
    ReactiveFormsModule,
    NgbDropdownModule, 
    NgbTooltipModule,
    InlineSVGModule,
    SharedModule
  ]
})
export class ProfileModule { }
