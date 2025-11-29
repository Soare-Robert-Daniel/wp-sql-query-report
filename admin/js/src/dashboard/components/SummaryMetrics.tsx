import { __ } from "@wordpress/i18n";
import type { QuerySummary } from "../types";

interface SummaryMetricsProps {
  summary: QuerySummary;
}

export function SummaryMetrics({ summary }: SummaryMetricsProps) {
  return (
    <div className="grid grid-cols-2 gap-3 mb-4">
      <div className="bg-white border border-gray-200 rounded-lg p-3">
        <div className="text-xs font-medium text-gray-500 uppercase">
          {__("Total Queries", "simple-sql-query-analyzer")}
        </div>
        <div className="text-xl font-bold text-gray-900 mt-1">{summary.total_queries}</div>
      </div>

      <div className="bg-white border border-gray-200 rounded-lg p-3">
        <div className="text-xs font-medium text-gray-500 uppercase">
          {__("Total Time", "simple-sql-query-analyzer")}
        </div>
        <div className="text-xl font-bold text-gray-900 mt-1">
          {summary.total_execution_time.toFixed(3)}s
        </div>
      </div>

      <div className="bg-white border border-gray-200 rounded-lg p-3">
        <div className="text-xs font-medium text-gray-500 uppercase">
          {__("Total Cost", "simple-sql-query-analyzer")}
        </div>
        <div className="text-xl font-bold text-gray-900 mt-1">
          {summary.total_cost.toLocaleString()}
        </div>
        <div className="text-xs text-gray-600 mt-1">{__("relative units", "simple-sql-query-analyzer")}</div>
      </div>

      {summary.has_warnings && (
        <div className="bg-orange-50 border border-orange-200 rounded-lg p-3">
          <div className="text-xs font-semibold text-orange-800">
            ⚠ {__("Warnings", "simple-sql-query-analyzer")}
          </div>
          <div className="text-xs text-orange-700 mt-1">
            {__("Check queries for issues", "simple-sql-query-analyzer")}
          </div>
        </div>
      )}
    </div>
  );
}
