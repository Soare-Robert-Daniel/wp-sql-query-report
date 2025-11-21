import { CopyButton } from './CopyButton';
import type { AnalysisResponse } from '../types';

interface AnalysisReportProps {
  response: AnalysisResponse;
}

export function AnalysisReport({ response }: AnalysisReportProps) {
  if (!response.data) {
    return null;
  }

  const { data } = response;
  const completeOutput = data.complete_output;

  return (
    <div className="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
      <div className="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <h3 className="text-lg font-semibold text-gray-900">SQL Query Analysis Report</h3>
      </div>

      <div className="p-6">
        <div className="mb-4 flex items-start justify-between">
          <div className="flex-1">
            <h4 className="text-sm font-medium text-gray-700 mb-2">Original Query</h4>
            <pre className="bg-gray-50 border border-gray-200 rounded p-3 text-sm font-mono text-gray-800 overflow-x-auto max-h-32">
              {data.query}
            </pre>
          </div>
        </div>

        <div className="mb-6 pb-6 border-b border-gray-200">
          <h4 className="text-sm font-medium text-gray-700 mb-2">Complete Analysis Output</h4>
          <div className="relative">
            <pre className="bg-gray-50 border border-gray-200 rounded p-4 text-xs font-mono text-gray-700 overflow-x-auto max-h-96 whitespace-pre-wrap break-words">
              {completeOutput}
            </pre>
          </div>
          <div className="mt-4">
            <CopyButton content={completeOutput} label="Copy Output" />
          </div>
        </div>

        {data.tables.length > 0 && (
          <div className="mb-6 pb-6 border-b border-gray-200">
            <h4 className="text-sm font-semibold text-gray-900 mb-3">Table Structures</h4>
            <div className="space-y-4">
              {data.tables.map((table) => (
                <div key={table.name} className="bg-gray-50 rounded p-4 border border-gray-200">
                  <h5 className="font-medium text-gray-900 mb-3">{table.name}</h5>
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b border-gray-300 bg-white">
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Column</th>
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Type</th>
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Null</th>
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Key</th>
                        </tr>
                      </thead>
                      <tbody>
                        {table.columns.map((col, idx) => (
                          <tr key={idx} className="border-b border-gray-200 hover:bg-gray-100">
                            <td className="px-3 py-2 font-mono text-gray-900">{col.name}</td>
                            <td className="px-3 py-2 text-gray-700">{col.type}</td>
                            <td className="px-3 py-2 text-gray-700">{col.null ? 'YES' : 'NO'}</td>
                            <td className="px-3 py-2 font-mono text-blue-600">{col.key || '-'}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {Object.keys(data.indexes).length > 0 && (
          <div className="mb-6">
            <h4 className="text-sm font-semibold text-gray-900 mb-3">Indexes</h4>
            <div className="space-y-4">
              {Object.entries(data.indexes).map(([tableName, indexes]) => (
                <div key={tableName} className="bg-gray-50 rounded p-4 border border-gray-200">
                  <h5 className="font-medium text-gray-900 mb-3">{tableName}</h5>
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b border-gray-300 bg-white">
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Index</th>
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Type</th>
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Column</th>
                          <th className="text-left px-3 py-2 font-medium text-gray-700">Unique</th>
                        </tr>
                      </thead>
                      <tbody>
                        {indexes.map((idx, i) => (
                          <tr key={i} className="border-b border-gray-200 hover:bg-gray-100">
                            <td className="px-3 py-2 font-mono text-gray-900">{idx.name}</td>
                            <td className="px-3 py-2 text-gray-700">{idx.type}</td>
                            <td className="px-3 py-2 font-mono text-gray-700">{idx.column}</td>
                            <td className="px-3 py-2">
                              <span
                                className={`inline-block px-2 py-1 rounded text-xs font-medium ${
                                  idx.unique ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800'
                                }`}
                              >
                                {idx.unique ? 'Yes' : 'No'}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
