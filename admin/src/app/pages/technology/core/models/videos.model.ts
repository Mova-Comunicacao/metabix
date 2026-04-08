export interface Videos {
    id: number;
    file_name: string;
    original_file_name: string;
    visible_to_customer: number;
    visible_full: number;
    subject: string;
    filetype: string;
    dateadded: string;
    description: string;
    external?: string;
    external_link?: string;
    thumbnail_link?: string;  
    thumb?: string;
    language?: {
        languageid: number;
        language: string;      
    }       
}