export interface Column {
  name: string;
  type: string;
  null: boolean;
  key: string;
  default: string | null;
}

export interface Table {
  name: string;
  columns: Column[];
}

export interface Index {
  name: string;
  type: string;
  unique: boolean;
  column: string;
  seq: number;
}

export interface Indexes {
  [tableName: string]: Index[];
}

export interface ExplainNode {
  operation: string;
  cost: number | null;
  estimated_rows: number | null;
  actual_time: {
    start: number;
    end: number;
    total: number;
  } | null;
  actual_rows: number | null;
  actual_loops: number | null;
  table: string | null;
  index: string | null;
  condition: string | null;
  depth: number;
  children: ExplainNode[];
}

export interface ExplainTree {
  root: ExplainNode | null;
}

export interface QueryInput {
  id: string;
  label: string;
  query: string;
}

export interface QueryResult {
  id: string;
  label: string;
  query: string;
  tables: Table[];
  indexes: Indexes;
  explain: Record<string, unknown>[];
  analyze: Record<string, unknown>[];
  execution_time?: number;
  error: string | null;
}

export interface QuerySummary {
  total_queries: number;
  total_execution_time: number;
  total_cost: number;
  slowest_query_index: number | null;
  has_warnings: boolean;
}

export interface AnalysisResponse {
  success: boolean;
  message: string;
  queries?: QueryResult[];
  summary?: QuerySummary;
  complete_output: string;
}

export interface SqlAnalyzerData {
  restRoot: string;
  restNonce: string;
  analyzeEndpoint: string;
  version: string;
  i18n: Record<string, string>;
}
