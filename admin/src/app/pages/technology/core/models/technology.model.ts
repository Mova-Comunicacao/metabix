export interface Technology {
    name: string;
    description: string;
    long_description: string;
    folder: string;  
    staffid?: number;
    date?: Date;
    language?: {
        languageid: number;
        language: string;      
    }    
}