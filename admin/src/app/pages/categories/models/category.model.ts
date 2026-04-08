import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Category extends ApiModel {
    name: string;
    description: string;
    folder?: string;
    file_name?: string;
    order?: number;
    slug?: string;
    date?: string | any;
    staff?: StaffModel;
    language?: {
        languageid: number;
        language: string;      
    } 
}