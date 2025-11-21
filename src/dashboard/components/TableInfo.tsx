import { useState } from '@wordpress/element';
import type { Table, Index } from '../types';

interface TableInfoProps {
  tables: Table[];
  indexes?: { [tableName: string]: Index[] };
}

export function TableInfo({ tables, indexes = {} }: TableInfoProps) {
  const [expandedTables, setExpandedTables] = useState<Set<string>>(
    new Set(tables.length === 1 ? [tables[0].name] : [])
  );

  const toggleTable = (tableName: string) => {
    const newExpanded = new Set(expandedTables);
    if (newExpanded.has(tableName)) {
      newExpanded.delete(tableName);
    } else {
      newExpanded.add(tableName);
    }
    setExpandedTables(newExpanded);
  };

  const getKeyBadge = (key: string) => {
    if (key === 'PRI') {
      return <span className="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-semibold rounded">PRI</span>;
    }
    if (key === 'UNI') {
      return <span className="inline-block px-2 py-0.5 bg-purple-100 text-purple-800 text-xs font-semibold rounded">UNI</span>;
    }
    if (key === 'MUL') {
      return <span className="inline-block px-2 py-0.5 bg-gray-100 text-gray-800 text-xs font-semibold rounded">IDX</span>;
    }
    return null;
  };

  return (
    <div>
      <h4 className="text-xs font-semibold text-gray-700 mb-3">
        Tables {tables.length > 1 && <span className="text-gray-500">({tables.length})</span>}
      </h4>

      <div className="space-y-3">
        {tables.map((table) => {
          const isExpanded = expandedTables.has(table.name);
          const tableIndexes = indexes[table.name] || [];

          return (
            <div key={table.name} className="border border-gray-200 rounded-lg overflow-hidden">
              {/* Table Header */}
              <button
                onClick={() => toggleTable(table.name)}
                className="w-full px-3 py-2 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition-colors"
              >
                <div className="flex items-center gap-2">
                  <span className="text-xs font-semibold text-gray-900 font-mono">{table.name}</span>
                  <span className="text-xs text-gray-500">
                    {table.columns.length} {table.columns.length === 1 ? 'col' : 'cols'}
                  </span>
                  {tableIndexes.length > 0 && (
                    <span className="text-xs text-gray-500">
                      {tableIndexes.length} {tableIndexes.length === 1 ? 'idx' : 'idxs'}
                    </span>
                  )}
                </div>
                <span className="text-sm text-gray-600">{isExpanded ? '▼' : '▶'}</span>
              </button>

              {/* Table Details */}
              {isExpanded && (
                <div className="px-3 py-2 space-y-3 bg-white border-t border-gray-200">
                  {/* Columns Section */}
                  <div>
                    <h5 className="text-xs font-semibold text-gray-700 mb-2">Columns</h5>
                    <div className="overflow-x-auto">
                      <table className="w-full text-xs">
                        <thead>
                          <tr className="border-b border-gray-200">
                            <th className="text-left px-2 py-1 text-gray-600 font-semibold">Name</th>
                            <th className="text-left px-2 py-1 text-gray-600 font-semibold">Type</th>
                            <th className="text-left px-2 py-1 text-gray-600 font-semibold">Constraints</th>
                            <th className="text-left px-2 py-1 text-gray-600 font-semibold">Default</th>
                          </tr>
                        </thead>
                        <tbody>
                          {table.columns.map((column, idx) => (
                            <tr key={idx} className="border-b border-gray-100 hover:bg-gray-50">
                              <td className="px-2 py-1.5 font-mono text-gray-900">{column.name}</td>
                              <td className="px-2 py-1.5 font-mono text-gray-700 text-xs">{column.type}</td>
                              <td className="px-2 py-1.5">
                                <div className="flex items-center gap-1 flex-wrap">
                                  {column.key && getKeyBadge(column.key)}
                                  {!column.null && (
                                    <span className="inline-block px-1.5 py-0.5 border border-green-300 text-green-700 text-xs rounded">
                                      NOT NULL
                                    </span>
                                  )}
                                </div>
                              </td>
                              <td className="px-2 py-1.5 text-gray-600 font-mono text-xs">
                                {column.default ? column.default : '—'}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>

                  {/* Indexes Section */}
                  {tableIndexes.length > 0 && (
                    <div className="pt-2 border-t border-gray-200">
                      <h5 className="text-xs font-semibold text-gray-700 mb-2">Indexes</h5>
                      <div className="space-y-1.5">
                        {tableIndexes
                          .reduce((acc, idx) => {
                            const existing = acc.find((i) => i.name === idx.name);
                            if (existing) {
                              existing.columns.push(idx.column);
                            } else {
                              acc.push({
                                name: idx.name,
                                type: idx.type,
                                unique: idx.unique,
                                columns: [idx.column],
                              });
                            }
                            return acc;
                          }, [] as Array<{ name: string; type: string; unique: boolean; columns: string[] }>)
                          .map((idx, i) => (
                            <div key={i} className="flex items-start gap-2 p-1.5 bg-gray-50 rounded text-xs">
                              <div className="flex-1">
                                <div className="flex items-center gap-2">
                                  <span className="font-mono font-semibold text-gray-900">{idx.name}</span>
                                  <span className="text-xs text-gray-500 bg-gray-200 px-1 rounded">{idx.type}</span>
                                  {idx.unique && (
                                    <span className="text-xs text-purple-700 bg-purple-100 px-1 rounded">UNIQUE</span>
                                  )}
                                </div>
                                <div className="text-gray-600 mt-0.5 font-mono">{idx.columns.join(', ')}</div>
                              </div>
                            </div>
                          ))}
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
