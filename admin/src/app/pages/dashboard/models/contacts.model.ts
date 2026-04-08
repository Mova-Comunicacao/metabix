import { ApiModel } from '../../../shared/crud-table';

export interface Contact extends ApiModel  {
    id: number; 
    firstname: string;
    lastname: string;
    phone: number;
    email: string;
    company: string;
    website: string;        
    address?: string;        
    altPhone?: string;        
    folder?: string;        
    createdate?: string | any;
    lastmodifieddate?: string;    
    updatedAt?: string;
    provider?: string;
    archived?: boolean; 
    status?: number;
}