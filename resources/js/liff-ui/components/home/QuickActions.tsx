import { CalendarDays, Coins, Coffee, Heart, Image as ImageIcon, MapPinned, QrCode, Stamp, Ticket, ShoppingBag } from "lucide-react";
import type { CSSProperties } from "react";
import { Link } from "react-router-dom";
import type { QuickAction } from "../../types/app";

const actionIconMap = {
  cup: Coffee,
  ticket: Ticket,
  stamp: Stamp,
  map: MapPinned,
  coins: Coins,
  heart: Heart,
  calendar: CalendarDays,
  image: ImageIcon,
  qr: QrCode,
} as const;

interface QuickActionsProps {
  actions: QuickAction[];
  columns: 2 | 3 | 4;
}

export function QuickActions({ actions, columns }: QuickActionsProps) {
  const sorted = [...actions].sort((a, b) => a.order - b.order).slice(0, 8);

  return (
    <section className="home-section" aria-labelledby="quick-actions-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="quick-actions-title">サービス</h2>
      </div>
      <div className="quick-grid" style={{ "--quick-columns": columns } as CSSProperties}>
        {sorted.map((action, index) => {
          const Icon = actionIconMap[action.icon as keyof typeof actionIconMap] ?? ShoppingBag;
          return (
            <Link key={action.id} to={`/${action.feature}`} className="quick-grid__item">
              <span className="quick-grid__index">0{index + 1}</span>
              <span className="quick-grid__icon">
                <Icon size={18} />
              </span>
              <span className="quick-grid__label">{action.label}</span>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
