import { StaffModel } from '../../../modules/auth';

export interface Goals {
    id: number;
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