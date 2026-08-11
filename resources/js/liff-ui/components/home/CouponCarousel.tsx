import { ChevronRight, Clock3, Ticket } from "lucide-react";
import { Link } from "react-router-dom";
import type { CouponItem } from "../../types/app";
import type { AppCustomization } from "../../types/customization";
import { Badge } from "../ui/Badge";
import { Card } from "../ui/Card";
import { EmptyState } from "../ui/EmptyState";

interface CouponCarouselProps {
  coupons: CouponItem[];
  config: AppCustomization["coupon"];
}

function isUpcoming(coupon: CouponItem) {
  if (!coupon.validFrom) return false;
  return new Date(`${coupon.validFrom}T00:00:00`).getTime() > Date.now();
}

export function CouponCarousel({ coupons, config }: CouponCarouselProps) {
  const visibleCoupons = coupons.filter((coupon) => config.showBeforeAvailable || !isUpcoming(coupon));

  if (visibleCoupons.length === 0) {
    return (
      <Card padding="lg">
        <EmptyState title="利用できるクーポンはありません" description="新しいクーポンが配信されるとここに表示されます。" />
      </Card>
    );
  }

  return (
    <section className="home-section" aria-labelledby="coupon-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="coupon-title">クーポン</h2>
        <Link className="section-head__link" to="/coupon">すべて見る</Link>
      </div>
      <div className="coupon-scroll">
        {visibleCoupons.map((coupon) => {
          const upcoming = isUpcoming(coupon);
          return (
            <Link key={coupon.id} className={`coupon-card${upcoming ? " is-upcoming" : ""}${config.showImage && coupon.image ? " coupon-card--with-image" : ""}`} to="/coupon">
              {config.showImage && coupon.image ? <img className="coupon-card__image" src={coupon.image} alt="" /> : <div className="coupon-card__icon"><Ticket size={20} aria-hidden="true" /></div>}
              <div className="coupon-card__content">
                <div className="coupon-card__title-row">
                  <h3 className="coupon-card__title">{coupon.title}</h3>
                  {config.showStatusBadge && upcoming ? <Badge>利用開始前</Badge> : coupon.featured ? <Badge>注目</Badge> : null}
                </div>
                {config.showDescription ? <p className="coupon-card__description">{coupon.description}</p> : null}
              </div>
              {config.showExpiryDate ? <div className="coupon-card__footer">
                <Clock3 size={14} />
                <span>{upcoming ? `${coupon.validFrom}から` : `期限 ${coupon.validUntil}`}</span>
                <ChevronRight size={14} aria-hidden="true" />
              </div> : null}
            </Link>
          );
        })}
      </div>
    </section>
  );
}
