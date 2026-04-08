import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';


import { LocalizeRouterModule } from '@gilsdav/ngx-translate-router';

import { LoginComponent } from './components/login/login.component';
import { LogoutComponent } from './components/logout/logout.component';
import { ForgotPasswordComponent } from './components/forgot-password/forgot-password.component';
import { NewPasswordComponent } from './components/new-password/new-password.component';

import { AuthComponent } from './auth.component';

const routes: Routes = [
  {
    path: '',
    component: AuthComponent,
    children: [
      {
        path: 'login',
        data: {
          title: 'Login',
          translate: 'nav.loginNav',
          returnUrl: window.location.pathname 
        }, 
        component: LoginComponent,
      },
      {
        path: 'forgot-password',
        data: {
          title: 'New Password',
          translate: 'nav.changePasswordlInfo',
        }, 
        component: ForgotPasswordComponent,
      },
      {
        path: 'new-password/:id/:password',    
        data: {
          title: 'New Password',
          translate: 'nav.changePasswordlInfo',
        },  
        component: NewPasswordComponent
      },       
      {
        path: 'logout',
        data: {
          title: 'Logout',
          translate: 'nav.logoutNav',
        },  
        component: LogoutComponent,
      },    
      { path: '', redirectTo: 'login', pathMatch: 'full' },
      { path: '**', redirectTo: 'login', pathMatch: 'full' },
    ],
  },
];

@NgModule({
  imports: [
    RouterModule.forChild(routes),
    LocalizeRouterModule.forChild(routes)
  ],
  exports: [RouterModule, LocalizeRouterModule],
})
export class AuthRoutingModule {}
