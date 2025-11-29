import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

interface DownloadButtonProps {
  content: string;
  label?: string;
}

export function DownloadButton({
  content,
  label = __("Download", "simple-sql-query-analyzer"),
}: DownloadButtonProps) {
  const [downloading, setDownloading] = useState(false);

  const generateHash = (str: string): string => {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = (hash << 5) - hash + char;
      hash = hash & hash;
    }
    return Math.abs(hash).toString(16).substring(0, 8);
  };

  const handleDownload = () => {
    try {
      setDownloading(true);

      // Generate filename with hash and date
      const hash = generateHash(content);
      const date = new Date();
      const dateString = date.toISOString().split("T")[0];
      const filename = `analyze_query_result_${hash}_${dateString}.txt`;

      // Create blob and download
      const blob = new Blob([content], { type: "text/plain;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);

      setDownloading(false);
    } catch {
      console.error(__("Failed to download file", "simple-sql-query-analyzer"));
      setDownloading(false);
    }
  };

  return (
    <button
      onClick={handleDownload}
      disabled={downloading}
      className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
    >
      <span>{downloading ? __("Downloading...", "simple-sql-query-analyzer") : label}</span>
    </button>
  );
}
