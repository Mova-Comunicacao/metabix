export interface ITableState {
  start_date?: string | null;
  end_date?: string | null;
  metrics: string[];
  dimensions: string[];
  // filtros/paginacão/ordenação opcionais
  filter?: any;
  paginator?: { page: number; pageSize: number };
  sorting?: { column: string; direction: 'asc' | 'desc' };
}

export interface TableResponseModel<T> {
    items: T[];
    total: number;
}