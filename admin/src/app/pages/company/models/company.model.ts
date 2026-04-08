export interface Company {
    name: string;
    description: string;
    long_description: string;
    folder: string;  
    staffid?: number;
    date?: string;
    language?: {
        languageid: number;
        language: string;      
    }
}