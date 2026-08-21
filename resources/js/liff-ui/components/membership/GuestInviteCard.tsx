import { QrCode } from "lucide-react";
import { Button } from "../ui/Button";

interface GuestInviteCardProps {
  kicker?: string;
  title: string;
  body: string;
  actionLabel: string;
  onAction: () => void;
}

export function GuestInviteCard({
  kicker = "MEMBER PASS",
  title,
  body,
  actionLabel,
  onAction,
}: GuestInviteCardProps) {
  return (
    <section className="guest-invite">
      <div className="guest-invite__pass">
        <span>{kicker}</span>
        <QrCode size={52} aria-hidden="true" />
      </div>
      <h1>{title}</h1>
      <p>{body}</p>
      <Button variant="solid" onClick={onAction}>{actionLabel}</Button>
    </section>
  );
}
