import { Injectable, OnDestroy } from '@angular/core';
import { Subject, Subscription } from 'rxjs';

import Swal, { SweetAlertOptions } from 'sweetalert2';

import { 
    Alert, 
    AlertType 
} from '../models/alert.model';

@Injectable({
    providedIn: 'root'
})
export class AlertService implements OnDestroy {

    private subject = new Subject<Alert>();
    private subscriptions: Subscription[] = [];

    constructor() {}

    // convenience methods
    public async fire(typeIcon: any = AlertType.Success, title?: string, message?: string, options?: SweetAlertOptions): Promise<any> {      
        const swalModal = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-light-primary btn-elevate fw-bold',
                cancelButton: 'btn btn-light btn-elevate fw-bold'
            },
            buttonsStyling: false,
        });
        const swal: any = await swalModal.fire({
            title: title,
            html: message, 
            icon: typeIcon, 
            showConfirmButton: true,
            showCloseButton: true,
            showCancelButton: false,
            ...options,
        });  
        this.subscriptions.push(swal);       

        return swal;
    }

    public async toast(typeIcon: any = AlertType.Success, message?: string, options?: SweetAlertOptions): Promise<any> {      
        const swal: any = await Swal.fire({toast: true, position: 'top', title: message, icon: typeIcon, showConfirmButton: false, timer: 5000,
            ...options,
        });
        this.subscriptions.push(swal);
        return swal;
    }

    ngOnDestroy() {
        this.subscriptions.forEach(sb => sb.unsubscribe());
    }
}