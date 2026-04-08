import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Slide extends ApiModel {
    name: string;
    description?: string;
    link: string;
    folder?: string;
    date?: string | any;   
    mask?: number;
    promo?: number;
    active?: number;
    staff?: StaffModel;
    language?: {
        languageid: number;
        language: string;      
    }
}