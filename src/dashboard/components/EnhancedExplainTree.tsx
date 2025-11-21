import { useState } from '@wordpress/element';

interface TreeLine {
  depth: number;
  operation: string;
  cost: number | null;
  costFormatted: string;
  rows: number | null;
  rowsFormatted: string;
  children: TreeLine[];
  rawLine: string;
}

interface EnhancedExplainTreeProps {
  rawExplain: string | null | undefined;
  isAnalyze?: boolean;
}

export function EnhancedExplainTree({ rawExplain, isAnalyze = false }: EnhancedExplainTreeProps) {
  const lines = parseExplainTree(rawExplain || '');

  if (!rawExplain || rawExplain.trim().length === 0) {
    return (
      <div className="p-3 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-xs">
        No execution plan available
      </div>
    );
  }

  if (lines.length === 0) {
    return (
      <div className="p-3 bg-gray-50 border border-gray-200 rounded text-gray-600 text-xs">
        Could not parse execution plan
      </div>
    );
  }

  // Expand all nodes by default
  const allExpandedNodes = getAllExpandedNodes(lines);
  const [expandedNodes, setExpandedNodes] = useState<Set<number>>(allExpandedNodes);

  const toggleNode = (index: number) => {
    const newExpanded = new Set(expandedNodes);
    if (newExpanded.has(index)) {
      newExpanded.delete(index);
    } else {
      newExpanded.add(index);
    }
    setExpandedNodes(newExpanded);
  };

  return (
    <div className="bg-white border border-gray-200 rounded-lg p-4">
      <div className="mb-3 pb-2 border-b border-gray-200">
        <h3 className="text-xs font-semibold text-gray-900">
          {isAnalyze ? 'Execution Plan (Actual)' : 'Execution Plan (Estimated)'}
        </h3>
      </div>

      {/* Legend */}
      <div className="mb-3 pb-2 border-b border-gray-200">
        <div className="text-xs text-gray-600 space-y-1">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-1">
              <span className="bg-green-100 text-green-800 px-1.5 py-0.5 rounded text-xs font-semibold whitespace-nowrap">Cost</span>
              <span className="text-gray-600">Query cost (relative units)</span>
            </div>
            <div className="flex items-center gap-1">
              <span className="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded text-xs font-semibold whitespace-nowrap">Rows</span>
              <span className="text-gray-600">Estimated output rows</span>
            </div>
          </div>
        </div>
      </div>

      <div className="font-mono text-xs space-y-0">
        {renderTreeLines(lines, 0, expandedNodes, toggleNode)}
      </div>
    </div>
  );
}

function getAllExpandedNodes(lines: TreeLine[]): Set<number> {
  const indices = new Set<number>();
  let currentIndex = 0;

  function traverse(nodeList: TreeLine[]) {
    for (const node of nodeList) {
      indices.add(currentIndex++);
      if (node.children.length > 0) {
        traverse(node.children);
      }
    }
  }

  traverse(lines);
  return indices;
}

function parseExplainTree(rawExplain: string): TreeLine[] {
  const lineStrings = rawExplain.split('\n').filter((line) => line.trim());
  const allLines: TreeLine[] = [];

  for (const line of lineStrings) {
    const parsed = parseLine(line);
    if (parsed) {
      allLines.push(parsed);
    }
  }

  // Build hierarchy
  return buildHierarchy(allLines);
}

function parseLine(line: string): TreeLine | null {
  // Count leading spaces and divide by 4 to get depth
  const leadingSpaces = line.length - line.trimLeft().length;
  const depth = Math.floor(leadingSpaces / 4);

  // Check if line starts with "->"
  if (!line.includes('->')) {
    return null;
  }

  // Extract operation and metrics
  const match = line.match(/->(.+?)(?:\s+\(([^)]+)\))?$/);
  if (!match) {
    return null;
  }

  const operation = match[1].trim();
  const metrics = match[2] || '';

  // Parse cost and rows
  let cost: number | null = null;
  let rows: number | null = null;

  const costMatch = metrics.match(/cost=([\d.e+]+)/);
  if (costMatch) {
    cost = parseFloat(costMatch[1]);
  }

  const rowsMatch = metrics.match(/rows=([\d.e+]+)/);
  if (rowsMatch) {
    rows = parseFloat(rowsMatch[1]);
  }

  return {
    depth,
    operation,
    cost,
    costFormatted: cost !== null ? formatNumber(cost) : '',
    rows,
    rowsFormatted: rows !== null ? formatNumber(rows) : '',
    children: [],
    rawLine: line,
  };
}

function buildHierarchy(lines: TreeLine[]): TreeLine[] {
  const root: TreeLine[] = [];
  const stack: (TreeLine | null)[] = [null]; // null represents root level

  for (const line of lines) {
    // Pop stack until we find parent at previous depth
    while (stack.length > line.depth + 1) {
      stack.pop();
    }

    // Ensure stack has enough entries
    while (stack.length <= line.depth) {
      stack.push(null);
    }

    // Add to parent
    const parent = stack[line.depth];
    if (parent === null) {
      root.push(line);
    } else {
      parent.children.push(line);
    }

    stack[line.depth + 1] = line;
  }

  return root;
}

function formatNumber(num: number): string {
  if (num >= 1e6) {
    return `${(num / 1e6).toFixed(2)}M`;
  } else if (num >= 1e3) {
    return `${(num / 1e3).toFixed(1)}K`;
  } else {
    return num.toFixed(0);
  }
}

function getCostColor(cost: number | null): string {
  if (cost === null) return 'bg-gray-100 text-gray-700';
  if (cost < 1000) return 'bg-green-100 text-green-800';
  if (cost < 100000) return 'bg-yellow-100 text-yellow-800';
  if (cost < 1000000) return 'bg-orange-100 text-orange-800';
  return 'bg-red-100 text-red-800';
}

function getOperationIcon(operation: string): string {
  const upper = operation.toUpperCase();
  if (upper.includes('TABLE SCAN')) return '📊';
  if (upper.includes('INDEX')) return '🔑';
  if (upper.includes('JOIN')) return '⛓️';
  if (upper.includes('FILTER')) return '🔍';
  if (upper.includes('SORT')) return '↗️';
  if (upper.includes('AGGREGATE')) return '📈';
  return '⚙️';
}

interface RenderTreeLinesProps {
  lines: TreeLine[];
  nodeIndex: number;
  expandedNodes: Set<number>;
  toggleNode: (index: number) => void;
}

function renderTreeLines(
  lines: TreeLine[],
  parentIndex: number,
  expandedNodes: Set<number>,
  toggleNode: (index: number) => void,
  depth: number = 0
): React.ReactNode {
  let globalIndex = parentIndex;

  return lines.map((line, idx) => {
    const currentIndex = globalIndex++;
    const hasChildren = line.children.length > 0;
    const isExpanded = expandedNodes.has(currentIndex);

    const depthColors = [
      'text-blue-700',
      'text-indigo-700',
      'text-purple-700',
      'text-pink-700',
      'text-red-700',
    ];
    const textColor = depthColors[line.depth % depthColors.length];

    return (
      <div key={`${depth}-${idx}`} className="relative">
        {/* Tree line */}
        <div
          className={`py-0.5 px-1 hover:bg-gray-50 rounded transition-colors cursor-pointer group`}
          style={{ paddingLeft: `${line.depth * 16 + 6}px` }}
        >
          {/* Expand/collapse button */}
          <div className="inline-flex items-center gap-1">
            {hasChildren ? (
              <button
                onClick={() => toggleNode(currentIndex)}
                className="flex-shrink-0 w-4 h-4 flex items-center justify-center text-gray-500 hover:text-gray-700 group-hover:bg-gray-200 rounded transition-colors"
              >
                <span className="text-xs">{isExpanded ? '▼' : '▶'}</span>
              </button>
            ) : (
              <div className="flex-shrink-0 w-4" />
            )}

            {/* Icon */}
            <span className="flex-shrink-0 text-sm">{getOperationIcon(line.operation)}</span>

            {/* Operation text */}
            <span className={`font-medium ${textColor} flex-1 text-xs`}>{line.operation}</span>

            {/* Cost badge */}
            {line.cost !== null && (
              <span
                className={`flex-shrink-0 px-1.5 py-0.5 rounded text-xs font-semibold whitespace-nowrap ${getCostColor(
                  line.cost
                )}`}
              >
                {line.costFormatted}
              </span>
            )}

            {/* Rows badge */}
            {line.rows !== null && (
              <span className="flex-shrink-0 px-1.5 py-0.5 rounded text-xs font-semibold whitespace-nowrap bg-gray-100 text-gray-800">
                {line.rowsFormatted}
              </span>
            )}
          </div>
        </div>

        {/* Children */}
        {hasChildren && isExpanded && (
          <div>
            {renderTreeLines(line.children, currentIndex + 1, expandedNodes, toggleNode, depth + 1)}
          </div>
        )}

        {/* Collapsed indicator */}
        {hasChildren && !isExpanded && (
          <div
            className="py-0.5 px-1 text-xs text-gray-500 italic group-hover:text-gray-700"
            style={{ paddingLeft: `${line.depth * 16 + 6 + 16 + 4}px` }}
          >
            ... {line.children.length} more
          </div>
        )}
      </div>
    );
  });
}
