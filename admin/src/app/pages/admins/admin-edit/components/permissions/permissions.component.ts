import { Component, EventEmitter, Input, OnDestroy, OnInit, Output } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { 
  RoleService,
  PermissionService
} from '../../../services';

import { Roles } from '../../../models/roles.model';
import { Permissions } from '../../../models/permissions.model';

import { Admin } from '../../../models/admin.model';

@Component({
  selector: 'app-permissions',
  templateUrl: './permissions.component.html'
})
export class PermissionsComponent implements OnInit, OnDestroy {
  @Input() staff!: Admin | any;

  @Output() permissionsValues = new EventEmitter<Permissions>();
  @Output() rolesValues = new EventEmitter<Roles>();

  roles: Roles[] = []
  permissions: Permissions[] = [];

  selectedPermissions: any;

  formPermissionGroup!: FormGroup;

  get permissionFormArr() {
    return this.formPermissionGroup.get('permissions') as FormArray;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    // Services
    public roleService: RoleService, 
    public permissionService: PermissionService, 
  ) { }

  ngOnInit(): void {
    this.selectedPermissions = this.staff?.permissions 
      ? [...this.staff.permissions] 
      : [];  

    this.loadRoles();
    this.loadPermissions();
    this.loadForm();
  }

  loadRoles() {
    const sb = this.roleService.getRoles()
    .subscribe(res => {
      this.roles = res as Roles[];
    });
    this.subscriptions.push(sb);        
  } 

  loadPermissions() {
    const sb = this.permissionService.getPermissions(this.staff.staffid)
    .subscribe(res => {
      this.permissions = res as Permissions[];       
    });
    this.subscriptions.push(sb);        
  }  

  loadForm() {
    this.formPermissionGroup =  this.fb.group({
      permissions: this.fb.array(
        this.selectedPermissions.map((p: { capability: any; feature: any; }) => this.fb.group({
          capability: new FormControl(p.capability),
          feature: new FormControl(p.feature)
        }))
      ),   
      role: [this.staff?.role.roleid ?? null], 
    });
  }

  // actions
  onChangeRole(e: any) {
    const value = e.target.value;
    this.rolesValues.emit(value);
  }

  onChangePermission(permission?: Permissions, e?: any) {
    const checked = e.target.checked;
    const checkedArray = this.formPermissionGroup.get('permissions') as FormArray;

    if (checked) {
      checkedArray.push(this.fb.group({
        capability: new FormControl(permission?.capability),
        feature: new FormControl(permission?.feature)        
      }));
    } else {
      const index = checkedArray.controls.findIndex(ctrl => 
        ctrl.value.capability === permission?.capability &&
        ctrl.value.feature === permission?.feature
      );
      if (index !== -1) {
        checkedArray.removeAt(index);
      }
    }

    this.permissionsValues.emit(checkedArray.value);
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
