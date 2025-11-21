import { Fragment } from '@wordpress/element';
import { Alert } from './Alert';
import { AnalysisReport } from './AnalysisReport';
import { EmptyState } from './EmptyState';
import type { AnalysisResponse } from '../types';

interface ResultsDisplayProps {
  loading: boolean;
  error: string | null;
  response: AnalysisResponse | null;
  onDismissError: () => void;
}

export function ResultsDisplay({
  loading,
  error,
  response,
  onDismissError,
}: ResultsDisplayProps) {
  return (
    <div className="space-y-4 h-full">
      {loading && (
        <div className="bg-white p-6 rounded-lg border border-gray-200 text-center">
          <div className="inline-flex items-center">
            <div className="animate-spin h-5 w-5 mr-3 border-2 border-blue-600 border-t-transparent rounded-full" />
            <span className="text-gray-700">Analyzing query...</span>
          </div>
        </div>
      )}

      {error && <Alert type="error" title="Error" message={error} onDismiss={onDismissError} />}

      {response && !error && (
        <Fragment>
          {response.success ? (
            <Alert type="success" title="Success" message={response.message} />
          ) : (
            <Alert
              type="error"
              title="Error"
              message={response.message}
              onDismiss={onDismissError}
            />
          )}
          {response.data && <AnalysisReport response={response} />}
        </Fragment>
      )}

      {!loading && !error && !response && <EmptyState />}
    </div>
  );
}
