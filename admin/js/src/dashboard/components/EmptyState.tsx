import { __ } from "@wordpress/i18n";

export function EmptyState() {
  return (
    <div className="flex items-center justify-center h-full min-h-[400px] bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-6">
      <div className="text-center max-w-sm">
        <div className="mb-4">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        </div>
        <h3 className="text-lg font-medium text-gray-900 mb-2">
          {__("No Analysis Yet", "simple-sql-query-analyzer")}
        </h3>
        <p className="text-sm text-gray-600 mb-4">
          {__(
            'Enter a SQL query on the left and click "Analyze Query" to see results here.',
            "simple-sql-query-analyzer",
          )}
        </p>
        <div className="bg-blue-50 border border-blue-200 rounded p-3">
          <p className="text-xs text-blue-800">
            💡 <strong>{__("Tip:", "simple-sql-query-analyzer")}</strong>{" "}
            {__(
              "Use SELECT queries only. The analyzer will show you execution plans, table structures, and index information.",
              "simple-sql-query-analyzer",
            )}
          </p>
        </div>
      </div>
    </div>
  );
}
