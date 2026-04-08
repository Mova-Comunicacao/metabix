import { Countries } from '../../../core';
import { StaffModel } from '../../../modules/auth';
import { ApiModel } from '../../../shared';

export interface Partners extends ApiModel {
    name: string;
    description: string;
    folder?: string;
    file_name?: string;
    external_link?: string;
    type: number;
    order?: number;
    date: Date;
    countries?: Countries;
    staff?: StaffModel;
}