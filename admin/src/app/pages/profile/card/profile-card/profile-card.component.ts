import { Component, OnInit } from '@angular/core';
import { map, Observable} from 'rxjs';

import { AuthService, UserType } from '../../../../modules/auth';

@Component({
  selector: 'app-profile-card',
  templateUrl: './profile-card.component.html',
})
export class ProfileCardComponent implements OnInit {

  staff$!: Observable<UserType>;

  constructor(public authService: AuthService) {
    this.staff$ = this.authService.currentUserSubject.asObservable();
  }

  ngOnInit(): void {}

  getAvatar(): Observable<string> {
    return this.staff$.pipe(
      map(staff => staff?.avatar || './assets/media/avatars/blank.png')
    );
  }
}
