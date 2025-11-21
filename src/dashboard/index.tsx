import { createRoot, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import './index.css';
import { QueryForm } from './components/QueryForm';
import { ResultsDisplay } from './components/ResultsDisplay';
import { Alert } from './components/Alert';
import type { AnalysisResponse, QueryInput } from './types';

const Dashboard = () => {
  const [queries, setQueries] = useState<QueryInput[]>([
    { id: '1', label: '', query: '' },
  ]);
  const [includeAnalyze, setIncludeAnalyze] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [response, setResponse] = useState<AnalysisResponse | null>(null);

  const analyzeQueries = useCallback(async () => {
    const validQueries = queries.filter((q) => q.query.trim());
    if (validQueries.length === 0) {
      setError('Please enter at least one SQL query');
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
          queries: validQueries.map((q) => ({
            id: q.id,
            label: q.label || `Query ${queries.indexOf(q) + 1}`,
            query: q.query.trim(),
          })),
          include_analyze: includeAnalyze,
        },
      });

      setResponse(data);
      if (!data.success) {
        setError(data.message);
      }
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : 'An error occurred while analyzing the queries';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  }, [queries, includeAnalyze]);

  const handleClear = useCallback(() => {
    setQueries([{ id: '1', label: '', query: '' }]);
    setIncludeAnalyze(false);
    setError(null);
    setResponse(null);
  }, []);

  const handleDismissError = useCallback(() => {
    setError(null);
  }, []);

  return (
    <div className="sql-analyzer-app bg-gray-100 min-h-screen py-8 px-4">
      <div className="">
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
          <div className="flex flex-col space-y-4">
            <QueryForm
              queries={queries}
              includeAnalyze={includeAnalyze}
              loading={loading}
              onQueriesChange={setQueries}
              onAnalyzeChange={setIncludeAnalyze}
              onSubmit={analyzeQueries}
              onClear={handleClear}
            />

            {/* Status Messages */}
            {loading && (
              <div className="bg-white p-4 rounded-lg border border-gray-200 text-center">
                <div className="inline-flex items-center">
                  <div className="animate-spin h-4 w-4 mr-2 border-2 border-blue-600 border-t-transparent rounded-full" />
                  <span className="text-sm text-gray-700">Analyzing {queries.filter(q => q.query.trim()).length} queries...</span>
                </div>
              </div>
            )}

            {error && <Alert type="error" title="Error" message={error} onDismiss={handleDismissError} />}

            {response && !error && (
              response.success ? (
                <Alert type="success" title="Success" message={response.message} />
              ) : (
                <Alert
                  type="error"
                  title="Error"
                  message={response.message}
                  onDismiss={handleDismissError}
                />
              )
            )}
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