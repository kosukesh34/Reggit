import { AlertTriangle } from "lucide-react";
import { Button } from "./Button";

interface ErrorStateProps {
  title: string;
  description: string;
  actionLabel?: string;
  onAction?: () => void;
}

export function ErrorState({ title, description, actionLabel, onAction }: ErrorStateProps) {
  return (
    <div className="empty-state">
      <div className="empty-state__title">
        <AlertTriangle size={16} />
        {title}
      </div>
      <div className="empty-state__description">{description}</div>
      {actionLabel && onAction ? (
        <Button variant="soft" onClick={onAction}>
          {actionLabel}
        </Button>
      ) : null}
    </div>
  );
}
