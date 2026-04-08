import { ApiModel } from '../../../shared';

export interface Social extends ApiModel  {
    name: string;
    link: string;
    order: number; 
    date?: string | any;
    active?: number;
    staff?: string; 
    teamid?: number;
}
