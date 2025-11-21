import { createRoot, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import './index.css';
import { QueryForm } from './components/QueryForm';
import { ResultsDisplay } from './components/ResultsDisplay';
import type { AnalysisResponse } from './types';

const Dashboard = () => {
  const [query, setQuery] = useState('');
  const [includeAnalyze, setIncludeAnalyze] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [response, setResponse] = useState<AnalysisResponse | null>(null);

  const analyzeQuery = useCallback(async () => {
    if (!query.trim()) {
      setError('Please enter a SQL query');
      return;
    }

    setLoading(true);
    setError(null);
    setResponse(null);

    try {
      const data: AnalysisResponse = await apiFetch({
        path: '/sql-analyzer/v1/analyze',
        method: 'POST',
        data: {
          query: query.trim(),
          include_analyze: includeAnalyze,
        },
      });

      setResponse(data);
      if (!data.success) {
        setError(data.message);
      }
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : 'An error occurred while analyzing the query';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  }, [query, includeAnalyze]);

  const handleClear = useCallback(() => {
    setQuery('');
    setIncludeAnalyze(false);
    setError(null);
    setResponse(null);
  }, []);

  const handleDismissError = useCallback(() => {
    setError(null);
  }, []);

  return (
    <div className="sql-analyzer-app bg-gray-100 min-h-screen py-8 px-4">
      <div className="max-w-7xl mx-auto">
        {/* Header - Full Width */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">SQL Analyzer</h1>
          <p className="text-gray-600">
            Analyze your SQL queries with detailed EXPLAIN results and database structure
            information
          </p>
        </div>

        {/* Two Column Layout - Desktop (lg:) single column by default */}
        <div className="grid grid-cols-1 lg:grid-cols-[45%_55%] gap-6">
          {/* Left Column - Query Form */}
          <div className="flex flex-col">
            <QueryForm
              query={query}
              includeAnalyze={includeAnalyze}
              loading={loading}
              onQueryChange={setQuery}
              onAnalyzeChange={setIncludeAnalyze}
              onSubmit={analyzeQuery}
              onClear={handleClear}
            />
          </div>

          {/* Right Column - Results Display */}
          <div className="flex flex-col">
            <ResultsDisplay
              loading={loading}
              error={error}
              response={response}
              onDismissError={handleDismissError}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

const root = createRoot(document.getElementById('dashboard')!);
root.render(<Dashboard />);