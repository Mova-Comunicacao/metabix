import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Social extends ApiModel  {
    id: number;
    name: string;
    link: string;
    order: number; 
    date: Date
    active?: number;
    staff?: StaffModel; 
}
