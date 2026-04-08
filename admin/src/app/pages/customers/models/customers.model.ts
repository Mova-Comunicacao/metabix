import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Customers extends ApiModel {
    name: string;
    description: string;
    folder?: string;
    file_name?: string;
    external_link?: string;
    order?: number;
    date: Date;
    staff?: StaffModel;
}