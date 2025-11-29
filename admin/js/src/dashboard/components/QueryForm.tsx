import { __, _n, sprintf } from "@wordpress/i18n";
import { QueryInput as QueryInputType } from "../types";
import { QueryInput } from "./QueryInput";

interface QueryFormProps {
  queries: QueryInputType[];
  includeAnalyze: boolean;
  loading: boolean;
  onQueriesChange: (queries: QueryInputType[]) => void;
  onAnalyzeChange: (include: boolean) => void;
  onSubmit: () => void;
  onClear: () => void;
}

export function QueryForm({
  queries,
  includeAnalyze,
  loading,
  onQueriesChange,
  onAnalyzeChange,
  onSubmit,
  onClear,
}: QueryFormProps) {
  const handleAddQuery = () => {
    const newQuery: QueryInputType = {
      id: Date.now().toString(),
      label: "",
      query: "",
    };
    onQueriesChange([...queries, newQuery]);
  };

  const handleRemoveQuery = (id: string) => {
    onQueriesChange(queries.filter((q) => q.id !== id));
  };

  const handleUpdateQuery = (id: string, field: "label" | "query", value: string) => {
    onQueriesChange(queries.map((q) => (q.id === id ? { ...q, [field]: value } : q)));
  };

  const hasValidQueries = queries.some((q) => q.query.trim());

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit();
      }}
      className="space-y-4"
    >
      {/* Queries List */}
      <div className="space-y-3">
        {queries.map((query, index) => (
          <QueryInput
            key={query.id}
            query={query}
            index={index}
            totalQueries={queries.length}
            onLabelChange={(value) => handleUpdateQuery(query.id, "label", value)}
            onQueryChange={(value) => handleUpdateQuery(query.id, "query", value)}
            onRemove={() => handleRemoveQuery(query.id)}
          />
        ))}
      </div>

      {/* Add Query Button */}
      <button
        type="button"
        onClick={handleAddQuery}
        disabled={loading}
        className="w-full py-2 px-4 border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:border-blue-500 hover:text-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {__("+Add Another Query", "sql-analyzer")}
      </button>

      {/* Options */}
      <div className="bg-white p-4 rounded-lg border border-gray-200">
        <div className="flex items-center mb-4">
          <input
            id="include-analyze"
            type="checkbox"
            checked={includeAnalyze}
            onChange={(e) => onAnalyzeChange(e.currentTarget.checked)}
            disabled={loading}
            className="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer"
          />
          <label htmlFor="include-analyze" className="ml-2 text-sm text-gray-700 cursor-pointer">
            {__("Include ANALYZE results for all queries", "sql-analyzer")}
          </label>
        </div>

        {/* Action Buttons */}
        <div className="flex gap-3">
          <button
            type="submit"
            disabled={loading || !hasValidQueries}
            className="px-6 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            {loading
              ? sprintf(
                  _n(
                    "Analyzing %d Query...",
                    "Analyzing %d Queries...",
                    queries.length,
                    "sql-analyzer",
                  ),
                  queries.length,
                )
              : sprintf(
                  _n(
                    "Analyze Query (%d)",
                    "Analyze All Queries (%d)",
                    queries.length,
                    "sql-analyzer",
                  ),
                  queries.length,
                )}
          </button>
          <button
            type="button"
            onClick={onClear}
            disabled={loading || !hasValidQueries}
            className="px-6 py-2 bg-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-400 disabled:bg-gray-200 disabled:cursor-not-allowed transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
          >
            {__("Clear All", "sql-analyzer")}
          </button>
        </div>
      </div>
    </form>
  );
}
