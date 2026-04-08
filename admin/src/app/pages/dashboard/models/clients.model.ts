export interface Client  {
    id?: number;
    clientId?: number;
    clientFirstName: string;
    clientLastName: string;
    clientAddress?: string;
    clientEmail?: string;
    clientPhone?: string;
    clientAltPhone?: string;
    clientDocsFolder?: string;
    clientAvatar?: string;
    createDate: string;   
    isActive?: number;
    oauth_provider?: string;
    total?: number;
}