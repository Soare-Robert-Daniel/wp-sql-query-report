import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

interface RawExplainOutputProps {
  data: Array<Record<string, unknown>>;
  isAnalyze?: boolean;
}

export function RawExplainOutput({ data, isAnalyze = false }: RawExplainOutputProps) {
  // Extract the raw output string from the data array
  const rawOutput = data && data.length > 0 ? (data[0]["EXPLAIN"] as string) : null;

  if (!rawOutput) {
    return null;
  }

  const title = isAnalyze
    ? __("Raw EXPLAIN ANALYZE Output", "simple-sql-query-analyzer")
    : __("Raw EXPLAIN Output", "simple-sql-query-analyzer");

  return (
    <div className="bg-white border border-gray-200 rounded-lg p-6">
      <div className="mb-4 pb-4 border-b border-gray-200">
        <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
      </div>

      <div className="bg-gray-50 border border-gray-200 rounded p-4 mb-4">
        <pre className="text-xs font-mono text-gray-700 overflow-x-auto max-h-96 whitespace-pre-wrap break-words leading-relaxed">
          {rawOutput}
        </pre>
      </div>

      {/* Copy Button */}
      <div className="flex justify-end">
        <CopyRawButton content={rawOutput} />
      </div>
    </div>
  );
}

interface CopyRawButtonProps {
  content: string;
}

function CopyRawButton({ content }: CopyRawButtonProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(content);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      console.error("Failed to copy to clipboard");
    }
  };

  return (
    <button
      onClick={handleCopy}
      className="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition-colors"
    >
      {copied
        ? __("Copied!", "simple-sql-query-analyzer")
        : __("Copy Output", "simple-sql-query-analyzer")}
    </button>
  );
}
