import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Items extends ApiModel {
    name: string;
    description: string;
    order?: number;
    date: Date;
    staff?: StaffModel;
    language?: {
        languageid: number;
        language: string;      
    }    
}