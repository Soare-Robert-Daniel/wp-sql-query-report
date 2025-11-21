interface QueryFormProps {
  query: string;
  includeAnalyze: boolean;
  loading: boolean;
  onQueryChange: (query: string) => void;
  onAnalyzeChange: (include: boolean) => void;
  onSubmit: () => void;
  onClear: () => void;
}

export function QueryForm({
  query,
  includeAnalyze,
  loading,
  onQueryChange,
  onAnalyzeChange,
  onSubmit,
  onClear,
}: QueryFormProps) {
  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    // Ctrl+Enter or Cmd+Enter to submit
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      onSubmit();
    }
  };

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit();
      }}
      className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm"
    >
      <div className="mb-4">
        <label htmlFor="sql-query" className="block text-sm font-medium text-gray-700 mb-2">
          SQL Query
        </label>
        <textarea
          id="sql-query"
          value={query}
          onChange={(e) => onQueryChange(e.currentTarget.value)}
          onKeyDown={handleKeyDown}
          placeholder="Enter your SQL query here... (Ctrl+Enter to submit)"
          className="w-full h-40 px-3 py-2 border border-gray-300 rounded-md font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          disabled={loading}
        />
      </div>

      <div className="mb-4 flex items-center">
        <input
          id="include-analyze"
          type="checkbox"
          checked={includeAnalyze}
          onChange={(e) => onAnalyzeChange(e.currentTarget.checked)}
          disabled={loading}
          className="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer"
        />
        <label htmlFor="include-analyze" className="ml-2 text-sm text-gray-700 cursor-pointer">
          Include ANALYZE results
        </label>
      </div>

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={loading || !query.trim()}
          className="px-6 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          {loading ? 'Analyzing...' : 'Analyze Query'}
        </button>
        <button
          type="button"
          onClick={onClear}
          disabled={loading || !query.trim()}
          className="px-6 py-2 bg-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-400 disabled:bg-gray-200 disabled:cursor-not-allowed transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
        >
          Clear
        </button>
      </div>
    </form>
  );
}
