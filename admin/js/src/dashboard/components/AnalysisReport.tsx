import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { CopyButton } from "./CopyButton";
import { DownloadButton } from "./DownloadButton";
import { QueryCard } from "./QueryCard";
import type { AnalysisResponse } from "../types";

interface AnalysisReportProps {
  response: AnalysisResponse;
}

type TabType = "visual" | "llm";

export function AnalysisReport({ response }: AnalysisReportProps) {
  const [activeTab, setActiveTab] = useState<TabType>("visual");

  if (!response.queries || !response.summary) {
    return null;
  }

  const { queries, complete_output } = response;

  return (
    <div className="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
      <div className="bg-gray-50 px-6 py-1 border-b border-gray-200 flex items-center justify-between">
        <h3 className="text-lg font-semibold text-gray-900 m-0">
          {__("SQL Query Analysis Report", "simple-sql-query-analyzer")}
        </h3>
        <CopyButton
          content={complete_output}
          label={__("Copy to LLM", "simple-sql-query-analyzer")}
        />
      </div>

      {/* Tabs */}
      <div className="flex border-b border-gray-200">
        <button
          onClick={() => setActiveTab("visual")}
          className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer ${
            activeTab === "visual"
              ? "border-blue-600 text-blue-600 bg-blue-50"
              : "border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300 hover:bg-gray-50"
          }`}
        >
          {__("Visual Analysis", "simple-sql-query-analyzer")}
        </button>
        <button
          onClick={() => setActiveTab("llm")}
          className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer ${
            activeTab === "llm"
              ? "border-blue-600 text-blue-600 bg-blue-50"
              : "border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300 hover:bg-gray-50"
          }`}
        >
          {__("LLM Export", "simple-sql-query-analyzer")}
        </button>
      </div>

      {/* Tab Content */}
      <div className="p-4">
        {/* Visual Analysis Tab */}
        {activeTab === "visual" && (
          <div className="space-y-4">
            {/* Summary Metrics */}
            {/* <SummaryMetrics summary={summary} /> */}

            {/* Query Cards */}
            <div className="space-y-3">
              {queries.map((query, index) => (
                <QueryCard
                  key={query.id}
                  query={query}
                  index={index}
                  totalQueries={queries.length}
                />
              ))}
            </div>
          </div>
        )}

        {/* LLM Export Tab */}
        {activeTab === "llm" && (
          <div className="space-y-4">
            <div className="bg-blue-50 border border-blue-200 rounded p-3">
              <p className="text-xs text-blue-800">
                💡 <strong>{__("Tip:", "simple-sql-query-analyzer")}</strong>{" "}
                {__(
                  "Copy the text below and paste it into your LLM chat for comprehensive query analysis and optimization suggestions.",
                  "simple-sql-query-analyzer",
                )}
              </p>
            </div>
            <div className="relative">
              <pre className="bg-gray-50 border border-gray-200 rounded p-4 text-xs font-mono text-gray-700 overflow-x-auto max-h-96 whitespace-pre-wrap break-words">
                {complete_output}
              </pre>
            </div>
            <div className="flex gap-3">
              <CopyButton
                content={complete_output}
                label={__("Copy for LLM", "simple-sql-query-analyzer")}
              />
              <DownloadButton
                content={complete_output}
                label={__("Download Report", "simple-sql-query-analyzer")}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
