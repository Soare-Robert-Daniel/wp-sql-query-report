import { __ } from "@wordpress/i18n";

interface AlertProps {
  type: "success" | "error";
  title: string;
  message: string;
  onDismiss?: () => void;
}

export function Alert({ type, title, message, onDismiss }: AlertProps) {
  const isError = type === "error";
  const bgColor = isError ? "bg-red-50" : "bg-green-50";
  const borderColor = isError ? "border-red-200" : "border-green-200";
  const textColor = isError ? "text-red-900" : "text-green-900";
  const titleColor = isError ? "text-red-700" : "text-green-700";
  const buttonHoverColor = isError ? "hover:bg-red-100" : "hover:bg-green-100";

  return (
    <div
      className={`${bgColor} ${borderColor} ${textColor} px-4 py-4 rounded-md border mb-4`}
      role="alert"
    >
      <div className="flex items-start justify-between">
        <div>
          <h3 className={`${titleColor} font-medium mb-1`}>{title}</h3>
          <p className="text-sm">{message}</p>
        </div>
        {onDismiss && (
          <button
            onClick={onDismiss}
            className={`${buttonHoverColor} ml-2 p-1 rounded transition-colors`}
            aria-label={__("Dismiss", "simple-sql-query-analyzer")}
          >
            <span className="text-lg leading-none">×</span>
          </button>
        )}
      </div>
    </div>
  );
}
