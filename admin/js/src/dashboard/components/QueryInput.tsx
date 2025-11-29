import type { QueryInput as QueryInputType } from "../types";

interface QueryInputProps {
  query: QueryInputType;
  index: number;
  totalQueries: number;
  onLabelChange: (value: string) => void;
  onQueryChange: (value: string) => void;
  onRemove?: () => void;
}

export function QueryInput({
  query,
  index,
  totalQueries,
  onLabelChange,
  onQueryChange,
  onRemove,
}: QueryInputProps) {
  const canRemove = totalQueries > 1;

  return (
    <div className="bg-white border border-gray-200 rounded-lg p-4">
      <div className="flex items-center justify-between mb-3">
        <div className="flex-1">
          <label className="block text-xs font-semibold text-gray-600 mb-1">
            Query {index + 1} Label (optional)
          </label>
          <input
            type="text"
            value={query.label}
            onChange={(e) => onLabelChange(e.target.value)}
            placeholder={`e.g., Main Posts Query`}
            className="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        {canRemove && (
          <button
            onClick={onRemove}
            className="ml-3 mt-6 px-3 py-2 text-red-600 hover:bg-red-50 rounded transition-colors text-sm font-medium"
            title="Remove query"
          >
            ✕
          </button>
        )}
      </div>

      <label className="block text-xs font-semibold text-gray-600 mb-1">SQL Query</label>
      <textarea
        value={query.query}
        onChange={(e) => onQueryChange(e.target.value)}
        placeholder="Enter your SQL query here..."
        className="w-full h-32 px-3 py-2 border border-gray-300 rounded text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
      />
    </div>
  );
}
