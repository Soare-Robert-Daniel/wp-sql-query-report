import { AnalysisReport } from "./AnalysisReport";
import { EmptyState } from "./EmptyState";
import type { AnalysisResponse } from "../types";

interface ResultsDisplayProps {
  loading: boolean;
  error: string | null;
  response: AnalysisResponse | null;
  onDismissError: () => void;
}

export function ResultsDisplay({ loading, error, response }: ResultsDisplayProps) {
  return (
    <div className="space-y-4 h-full">
      {response && response.queries && <AnalysisReport response={response} />}

      {!loading && !error && !response && <EmptyState />}
    </div>
  );
}
