import { ApiModel } from '../../../../shared';

export interface Activity extends ApiModel {
    id: number;
    project_id: number;
    project_name: string;
    description: string;
    fullname: string;
    dateadded: string | any;
    additional_data?: string;
    staff_id: number;
}