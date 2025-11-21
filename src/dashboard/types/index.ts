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

export interface AnalysisResponse {
  success: boolean;
  message: string;
  data?: {
    query: string;
    tables: Table[];
    indexes: Indexes;
    explain: Record<string, unknown>[];
    analyze: Record<string, unknown>[];
    complete_output: string;
  };
}

export interface SqlAnalyzerData {
  restRoot: string;
  restNonce: string;
  analyzeEndpoint: string;
  version: string;
  i18n: Record<string, string>;
}
