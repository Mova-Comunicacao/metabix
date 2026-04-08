export interface DateRange {
    start_date: any;
    end_date: any;
    metrics: string;
    dimensions: string;
}

export interface MetricPoint { 
    name: string; 
    value: number; 
}
export interface MetricResponse { 
    series: MetricPoint[]; 
    totals: number;
}