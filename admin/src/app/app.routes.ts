import { Routes } from '@angular/router';

import { AuthGuard } from './modules/auth/services/auth.guard';

export const routes: Routes = [
  {
    path: 'auth',
    data: {
      reuse: false,
    },    
    loadChildren: () =>
      import('./modules/auth/auth.module').then((m) => m.AuthModule),
  },      
  {
    path: '',
    canActivate: [AuthGuard],
    loadChildren: () =>
      import('./layout/layout.module').then((m) => m.LayoutModule),
  },
  {
    path: 'error',
    data: {
      reuse: false,
    },
    loadChildren: () =>
      import('./modules/errors/errors.module').then((m) => m.ErrorsModule),
  },    
  { path: '**', redirectTo: 'error' },      
];
