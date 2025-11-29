import { useState } from "@wordpress/element";
import { EnhancedExplainTree } from "./EnhancedExplainTree";
import { TableInfo } from "./TableInfo";
import { __ } from "@wordpress/i18n";
import type { QueryResult } from "../types";

interface QueryCardProps {
  query: QueryResult;
  index: number;
  totalQueries: number;
}

export function QueryCard({ query, index }: QueryCardProps) {
  const [isExpanded, setIsExpanded] = useState(index === 0);

  if (query.error) {
    return (
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="px-4 py-3 bg-red-50 border-b border-red-200">
          <h3 className="text-sm font-semibold text-red-900">{query.label}</h3>
        </div>
        <div className="p-4">
          <p className="text-sm text-red-700">
            {__("Error:", "simple-sql-query-analyzer")} {query.error}
          </p>
          <pre className="mt-3 bg-gray-50 p-3 rounded text-xs font-mono overflow-x-auto text-gray-700 max-h-32">
            {query.query}
          </pre>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <button
        onClick={() => setIsExpanded(!isExpanded)}
        className="w-full text-left px-4 py-3 bg-blue-50 border-b border-gray-200 hover:bg-blue-100 transition-colors flex items-center justify-between"
      >
        <div>
          <h3 className="text-sm font-semibold text-gray-900 m-0">
            {query.label}
            <span className="text-xs text-gray-600 ml-2">
              ({query.execution_time ? `${(query.execution_time * 1000).toFixed(1)}ms` : __("Pending", 'simple-sql-query-analyzer')})
            </span>
          </h3>
        </div>
        <span className="text-xl">{isExpanded ? "▼" : "▶"}</span>
      </button>

      {isExpanded && (
        <div className="p-4 space-y-4">
          {/* Query Text */}
          <div>
            <h4 className="text-xs font-semibold text-gray-700 mb-2">
              {__("Query", "simple-sql-query-analyzer")}
            </h4>
            <pre className="bg-gray-50 p-3 rounded text-xs font-mono overflow-x-auto text-gray-700 max-h-32">
              {query.query}
            </pre>
          </div>

          {/* Execution Plans */}
          {query.analyze && query.analyze.length > 0 && (
            <EnhancedExplainTree
              rawExplain={query.analyze[0]["EXPLAIN"] as string}
              isAnalyze={true}
            />
          )}

          {query.explain && query.explain.length > 0 && (
            <EnhancedExplainTree
              rawExplain={query.explain[0]["EXPLAIN"] as string}
              isAnalyze={false}
            />
          )}

          {/* Tables */}
          {query.tables.length > 0 && <TableInfo tables={query.tables} indexes={query.indexes} />}
        </div>
      )}
    </div>
  );
}
