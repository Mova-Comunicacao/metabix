import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, map, switchMap, take, tap } from 'rxjs/operators';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';

import { AuthService } from '../../../modules/auth';
import { 
  SettingsService, 
} from '../../../core';

import { TeamService } from '../services';
import { Teams } from '../models/teams.model';

import { DeleteAvatarComponent } from '../components/delete-avatar/delete-avatar.component';

const EMPTY_TEAM: Teams = {
  id: 0,
  name: '',
  description: '',
  phonenumber: '',
  email: '',
  folder: '',
  type: 0,
  file_avatar: '',
  date: new Date
};

@Component({
  selector: 'app-team-edit',
  templateUrl: './team-edit.component.html',
})
export class TeamEditComponent implements OnInit, OnDestroy {
  id?: number;
  staffid?: number;

  hasErro?: boolean = false;
  errorMessage?: string = ''

  team!: Teams;
  previous!: Teams;

  formGroup!: FormGroup;

  // Getters
  get team$() {
    return this.teamService.items$;
  }
  get settings$() {
    return this.settings.settings$;
  }  
  get isLoading$() {
    return this.teamService.isLoading$;
  }  

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,    
    private modalService: NgbModal,
    private cdr: ChangeDetectorRef,  
    private localize: LocalizeRouterService,
    private route: ActivatedRoute,    
    private router: Router,      
    // Services
    private authService: AuthService,
    private settings: SettingsService, 
    private teamService: TeamService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadTeam();    
  }

  loadTeam() {
    const sb = this.route.paramMap.pipe(
      take(1),
      switchMap(params => {
        // get id from URL
        this.id = Number(params.get('id'));
        return this.id && this.id > 0
          ? this.teamService.getItemById(this.id)
          : of(EMPTY_TEAM);
      }),
      catchError((err) => {
        this.errorMessage = err;
        return of(undefined);
      }),    
      finalize(() => {
        this.cdr.markForCheck();        
      })                 
    ).subscribe((res) => {
      if (!res) {
        this.router.navigate(
          [this.localize.translateRoute(['/teams']), { relativeTo: this.route }]
        );  
        
        return;
      }

      this.team = res as Teams;
      this.previous = { ...this.team }

      this.loadForm();
    });
    this.subscriptions.push(sb);
  }  

  loadForm() {
    if (!this.team) {
      return;
    } 

    this.formGroup = this.fb.group({
      name: [this.team.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      description: [this.team.description, Validators.compose([
        Validators.required,
        Validators.minLength(3),
      ])],     
      phonenumber: [this.team.phonenumber, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3)
      ])],
      email: [this.team.email, Validators.compose([
        Validators.nullValidator, 
        Validators.email,
        Validators.minLength(3),
      ])],          
      type: [this.team.type, Validators.compose([
        Validators.required,
      ])],               
      file_avatar: [this.team.file_avatar],  
      languageid: [this.team.language?.languageid],                         
      staffid: [this.staffid],                 
      id: [this.team.id],               
    });    
  }   
  
  // Actions
  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.team = { ...this.team, ...this.formGroup.value };

    if (this.team.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  // actions
  edit() {
    const sbUpdate = this.teamService.update(this.team).pipe(
      finalize(() => {
        this.cdr.markForCheck();
      }),         
      catchError((err) => {
        console.error('UPDATE ERROR', err);
        return of(this.team);
      }),
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.teamService.create(this.team).pipe(
      finalize(() => {
        this.cdr.markForCheck();
      }),         
      catchError((err) => {
        console.error('CREATE ERROR', err);
        return of(this.team);
      }),
    ).subscribe((res) => {
      const id = res.data?.id;
       if (id) {
        this.router.navigate([this.localize.translateRoute(`/teams/edit/${id}`)]);        
       } 
    });
    this.subscriptions.push(sbCreate);
  } 

  editExit() {
    this.team =  { ...this.team, ...this.formGroup.value }

    const sbUpdate = this.teamService.update(this.team).pipe(
      tap(() => this.router.navigate([this.localize.translateRoute(`/teams/`)])),
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(this.team);
      })
    ).subscribe();
    this.subscriptions.push(sbUpdate);    
  }    

  // Avatar
  onSelectedFile(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.formGroup.get('file_avatar')?.setValue(input.files[0]);
      this.uploadPicture();
    }    
  }

  private uploadPicture(): void {
    const formData = new FormData();
    formData.append('file_avatar', this.formGroup.get('file_avatar')?.value);
    formData.append('id', this.formGroup.get('id')?.value);

    const sb = this.teamService.uploadPicture(formData).pipe(
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(undefined);
      }),      
    ).subscribe((res) => {
      if(!res?.ok) {
        this.errorMessage = res?.alert?.message ?? 'Error uploading file';
        return;         
      }      
      this.updateCurrentTeam({ ...this.team, file_avatar: res.data?.file_avatar });
      this.errorMessage = '';       
    });
    this.subscriptions.push(sb);   
  }    

  deleteImage(): void {  
    const modalRef = this.modalService.open(DeleteAvatarComponent);
    modalRef.componentInstance.teamid = this.id;
    modalRef.closed.subscribe(() => this.updateCurrentTeam({ ...this.team, file_avatar: '' }));
  } 

  getAvatar(): string {
    return this.team.file_avatar
      ? `url('${this.team.folder}${this.team.file_avatar}')`
      : `url('./assets/media/avatars/blank.png')`;
  }

  private updateCurrentTeam(team: Teams) {
    this.team = { ...team };
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }
    
  // helpers for View
  isControlValid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.valid && (control.dirty || control.touched);
  }

  isControlInvalid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.invalid && (control.dirty || control.touched);
  }

  controlHasError(validation: string, controlName: string) {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  }   
}
