import {  NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormsModule } from '@angular/forms';
import { provideHttpClient, withInterceptorsFromDi } from '@angular/common/http';

// 3rd-Party plugins variables
import { LocalizeRouterModule } from '@gilsdav/ngx-translate-router';
import { TranslateModule } from '@ngx-translate/core';

import { 
  KeeniconModule 
} from '../../shared';

// Components
import { LoginComponent } from './components/login/login.component';
import { LogoutComponent } from './components/logout/logout.component';
import { ForgotPasswordComponent } from './components/forgot-password/forgot-password.component';
import { NewPasswordComponent } from './components/new-password/new-password.component';

import { AuthRoutingModule } from './auth-routing.module';
import { AuthComponent } from './auth.component';


@NgModule({ declarations: [
        AuthComponent,
        LoginComponent,
        ForgotPasswordComponent,
        NewPasswordComponent,
        LogoutComponent,
    ], imports: [CommonModule,
        AuthRoutingModule,
        FormsModule,
        ReactiveFormsModule,
        LocalizeRouterModule,
        TranslateModule,
        KeeniconModule], providers: [provideHttpClient(withInterceptorsFromDi())] })
export class AuthModule { }
