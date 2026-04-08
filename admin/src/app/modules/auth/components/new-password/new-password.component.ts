import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { first, Observable, Subscription } from 'rxjs';

import { ConfirmPasswordValidator } from '../confirm-password/confirm-password.validator';
import { PasswordService } from '../../services/password.service';
import { ActivatedRoute, Router } from '@angular/router';

enum ErrorStates {
  NotSubmitted,
  HasError,
  NoError,
}

@Component({
  selector: 'app-new-password',
  templateUrl: './new-password.component.html',
  styleUrls: ['./new-password.component.scss']
})
export class NewPasswordComponent implements OnInit {
  newPasswordForm!: FormGroup;
  
  errorState: ErrorStates = ErrorStates.NotSubmitted;
  errorStates = ErrorStates;

  showPassword = false;
  currentPassword!: string;

  isLoading$: Observable<boolean>;

  // private fields
  private unsubscribe: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,  
    private router: Router,
    // Services
    private passwordService: PasswordService    
  ) { 
    this.isLoading$ = this.passwordService.isLoading$;
  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.currentPassword = params['password'];
    });      
    this.initForm();
  }

  // convenience getter for easy access to form fields
  get f() {
    return this.newPasswordForm.controls;
  }

  initForm() {
    this.newPasswordForm = this.fb.group({
      currentPassword: [this.currentPassword, Validators.required],
      password: ['', Validators.compose([
        Validators.required,
        Validators.minLength(3),
      ])],
      cPassword: ['', Validators.compose([
        Validators.required,
      ])]
    }, {
      validator: ConfirmPasswordValidator.MatchPassword
    });     
  }


  submit() {
    this.newPasswordForm.markAllAsTouched();
    if (!this.newPasswordForm.valid) {
      return;
    }

    this.errorState = ErrorStates.NotSubmitted;
    const formValues = this.newPasswordForm.value;
    // prepar user
    const formData = {
      currentPassword: this.f['currentPassword'].value,
      password: this.f['password'].value
    };    
    const sb = this.passwordService
      .newPassword(formData)
      .pipe(first())
      .subscribe((result: any) => {
        if (result && result.type === 'success') {
          this.router.navigate(['/auth/login']);
        }        
        this.errorState = result.type == 'success' ? ErrorStates.NoError : ErrorStates.HasError;
    });
    this.unsubscribe.push(sb);
  } 

  showHidePassword() {
    this.showPassword = !this.showPassword;
  }   
    
}
