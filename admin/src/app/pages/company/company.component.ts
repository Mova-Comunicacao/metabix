import { Component, OnInit, OnDestroy, ViewChild, ElementRef, ChangeDetectorRef } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, CdkDragEnter, CdkDragMove, CdkDragStart, CdkDropList, moveItemInArray } from '@angular/cdk/drag-drop';

import { AuthService } from '../../modules/auth';

import { CompanyService } from './services';
import { Company, Picture } from './models';

import { UploadImageComponent } from './components/upload-image/upload-image.component';
import { DeleteImageComponent } from './components/delete-image/delete-image.component';

@Component({
  selector: 'app-company',
  templateUrl: './company.component.html',
})
export class CompanyComponent implements OnInit, OnDestroy {
  @ViewChild('dropListContainer', { static: false }) dropListContainer?: ElementRef<HTMLElement>;

  formGroup!: FormGroup;

  pictures: Picture[] = [];

  company!: Company;
  firstCompanyState!: Company;

  isLoading?: boolean;

  errorMessage = '';
  staffid?: number;

  activeTabId = 1;
    
  // Getters
  get company$() {
    return this.companyService.items$;
  }

  get isLoading$() {
    return this.companyService.isLoading$;
  }

  private subscriptions: Subscription[] = [];
  
  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private modalService: NgbModal,
    // Services
    private authService: AuthService,
    private companyService: CompanyService,
  ) {
    this.staffid = this.authService.currentUserValue?.staffid;
   }

  ngOnInit(): void {
    this.loadPictures();
    this.loadCompany(); 
  }

  private loadCompany() {
    const sb = this.companyService.getCompany().pipe(
    ).subscribe({
      next: (res: Company) => {
        this.company = res as Company;
        this.firstCompanyState = { ...this.company };
        this.loadForm();
      },
      error: (err) => {
        console.error('LOAD ERROR:', err);
      }
    });

    this.subscriptions.push(sb);
  }  

  private loadPictures() {
    this.isLoading = true;
    const sb = this.companyService.getPictures().pipe(
      finalize(() => this.isLoading = false)
    ).subscribe(res => {
      this.pictures = res as Picture[];
    });
    this.subscriptions.push(sb);    
  }   

  loadForm() {
    if (!this.company) {
      return;
    } 

    this.formGroup = this.fb.group({
      name: [this.company.name, Validators.compose([
        Validators.required,
        Validators.minLength(3)
      ])],
      description: [this.company.description, Validators.compose([
        Validators.required,
        Validators.minLength(3),
        Validators.maxLength(250)
      ])],
      long_description: [this.company.long_description, Validators.compose([
        Validators.required,
        Validators.minLength(3)
      ])],
      staffid: [this.staffid],
      languageid: [this.company.language?.languageid]
    });
  }  

  // actions
  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.company = { ...this.company, ...this.formGroup.value }

    this.isLoading = true;
    const sbUpdate = this.companyService.update(this.company).pipe(
      finalize(() => {
        this.isLoading = false;
        this.cdr.markForCheck();
      }),        
      catchError((err) => {
        console.error('UPDATE ERROR', err);
        return of(this.company);
      })
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }  

  upload(): void {
    const modalRef = this.modalService.open(UploadImageComponent, {size: 'lg'});
    modalRef.closed.subscribe(() => this.loadPictures());
  }  

  deletePicture(id: number): void {  
    const modalRef = this.modalService.open(DeleteImageComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.loadPictures());
  }    

  sortable(data: Picture[]) {
    const sb = this.companyService.sortable(data).pipe(
      catchError((err) => {
        console.log(err);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sb);
  }    

  // Dragging
  private dropListReceiverElement?: HTMLElement;
  private dragDropInfo?: { dragIndex: number; dropIndex: number };

  dragEntered(event: CdkDragEnter<any>) {
    const drag = event.item;
    const dropList = event.container;
    const dragIndex = drag.data as number;
    const dropIndex = dropList.data as number;

    this.dragDropInfo = { dragIndex, dropIndex };

    const dragEl = drag.element.nativeElement as HTMLElement;
    const phContainer = dropList.element.nativeElement as HTMLElement;
    const phElement = phContainer.querySelector('.cdk-drag-placeholder') as HTMLElement | null;

    if (phElement) {
      phElement.style.width = `${dragEl.offsetWidth}px`;
      phElement.style.height = `${dragEl.offsetHeight}px`;

      phContainer.removeChild(phElement);
      phContainer.parentElement?.insertBefore(phElement, phContainer);

      moveItemInArray(event.container.data, dragIndex, dropIndex);
    }
  } 

  dragMoved(event: CdkDragMove<number>) {
    if (!this.dropListContainer || !this.dragDropInfo) return;

    const phContainer = this.dropListContainer.nativeElement as HTMLElement;
    const phElement = phContainer.querySelector('.cdk-drag-placeholder') as HTMLElement | null;
    if (!phElement) return;

    const receiverElement =
      this.dragDropInfo.dragIndex > this.dragDropInfo.dropIndex
        ? (phElement.nextElementSibling as HTMLElement | null)
        : (phElement.previousElementSibling as HTMLElement | null);

    if (!receiverElement) return;

    receiverElement.classList.add('cdk-drag-receiver-hidden');
    this.dropListReceiverElement = receiverElement;
  }

  dragDropped(event: CdkDragDrop<Picture[], Picture[], number>) {
    if (this.dropListReceiverElement) {
      this.dropListReceiverElement.classList.remove('cdk-drag-receiver-hidden');
      this.dropListReceiverElement = undefined;
    }
    this.dragDropInfo = undefined;

    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      requestAnimationFrame(() => this.sortable(event.container.data));
    }
  }

  reset() {
    if (!this.firstCompanyState) {
      return;
    }

    this.company = Object.assign({}, this.firstCompanyState);
    this.loadForm();
  }    

  setActiveTab(tabId: number) {
    this.activeTabId = tabId;
  }

  getActiveTabCSSClass(tabId: number) {
    if (tabId !== this.activeTabId) {
      return '';
    }

    return 'active';
  }    

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
    //if (this.persistRaf) cancelAnimationFrame(this.persistRaf);
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
