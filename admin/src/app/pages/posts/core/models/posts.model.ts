import { StaffModel } from '../../../../modules/auth';
import { ApiModel } from '../../../../shared';

import { Category } from './category.model';

export interface Posts extends ApiModel {
    name: string;
    description: string;
    long_description: string;
    date: string;
    staffid?: number;
    clientid?: number;
    folder: string;
    active: number;
    categories?: Category[];
    staff?: StaffModel;  
    external_link?: string;
    language?: {
        languageid: number;
        language: string;      
    }  
}
