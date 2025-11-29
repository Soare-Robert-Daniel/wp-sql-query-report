import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

interface CopyButtonProps {
  content: string;
  label?: string;
}

export function CopyButton({ content, label = __("Copy", "simple-sql-query-analyzer") }: CopyButtonProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(content);
      setCopied(true);

      // Reset after 2 seconds
      setTimeout(() => setCopied(false), 2000);
    } catch {
      console.error(__("Failed to copy to clipboard", "simple-sql-query-analyzer"));
    }
  };

  return (
    <button
      onClick={handleCopy}
      className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
    >
      <span>{copied ? __("Copied!", "simple-sql-query-analyzer") : label}</span>
    </button>
  );
}
