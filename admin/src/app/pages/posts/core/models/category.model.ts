export interface Category {
    id: number;
    name: string;
    description: string;  
    folder?: string;
    file_name?: string;    
    order: number;  
}