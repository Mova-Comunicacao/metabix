import { ApiModel } from '../../../shared';

import { Roles } from './roles.model';
import { Permissions } from './permissions.model';

export interface Admin extends ApiModel {
    staffid: number;
    username: string;
    password: string;
    fullname?: string;
    admin: number;
    email: string;
    token: string;
    address: string;
    avatar?: string;
    role?: Roles;
    permissions: Permissions;
    occupation: string;
    company: string;
    phone: string;
    // personal information
    firstname: string;
    lastname: string;
    website: string;
    // account information
    default_language: string;
    timeZone: string;
    date: string;
    active: number;
}
