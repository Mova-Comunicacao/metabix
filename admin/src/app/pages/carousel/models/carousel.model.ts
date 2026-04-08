import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Carousel extends ApiModel {
    name: string;
    file_name?: string;
    original_file_name?: string;
    description: string;
    folder?: string;
    date?: Date;   
    type?: 'picture' | 'video';
    url?: string;
    thumb?: string;
    active?: number;
    staff?: StaffModel;
    language?: {
        languageid: number;
        language: string;      
    }
}