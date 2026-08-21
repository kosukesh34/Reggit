import {
  Check,
  ChevronRight,
  Clock3,
  Coffee,
  Gift,
  Heart,
  Hotel,
  Map as MapIcon,
  MapPin,
  PawPrint,
  Plus,
  QrCode,
  Store,
  TicketPercent,
} from "lucide-react";
import type { CSSProperties, ReactNode } from "react";
import { themeMap } from "../themes/themes";
import {
  industryBlockLabels,
  resolveIndustryGalleryTemplate,
  type IndustryBlockType,
  type IndustryGalleryTemplate,
  type IndustryVisualStyle,
} from "../config/industryGallery";
import { useAppConfig } from "../context/AppConfigContext";
import { resolveCustomizationColors } from "../utils/customization";

interface BlockDesign {
  appName: string;
  image: string;
  style: IndustryGalleryTemplate["style"];
  primary: string;
  accent: string;
  radius: number;
  items: IndustryGalleryTemplate["items"];
  news: IndustryGalleryTemplate["news"];
  stampCurrent: number;
  stampTotal: number;
  couponTitle: string;
  couponDesc: string;
  memberId: string;
  memberPoints: number;
}

const visualStyles: IndustryVisualStyle[] = [
  "modern", "editorial", "natural", "dining", "warm", "elegant",
  "clinical", "active", "luxury", "utility", "playful", "neon",
];

function resolveVisualStyle(value: string | undefined, fallback: IndustryVisualStyle): IndustryVisualStyle {
  return visualStyles.includes(value as IndustryVisualStyle) ? value as IndustryVisualStyle : fallback;
}

function FeatureCard({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="igb-card">
      <header>
        <h3>{title}</h3>
        <span>
          すべて見る
          <ChevronRight size={12} />
        </span>
      </header>
      {children}
    </section>
  );
}

function IndustryFeatureBlock({ type, design }: { type: IndustryBlockType; design: BlockDesign }) {
  const imageStyle = { backgroundImage: `url("${design.image}")` } as CSSProperties;

  switch (type) {
    case "hero":
      return (
        <section className={`igb-hero igb-hero--${design.style}`} style={{ backgroundImage: `linear-gradient(180deg, transparent 10%, rgba(0, 0, 0, 0.72)), url("${design.image}")` }}>
          <span>MEMBERS APP</span>
          <div>
            <h2>{design.appName}</h2>
            <p>店舗での体験を、もっと便利で特別なものに。</p>
            <em>
              詳しく見る
              <ChevronRight size={14} />
            </em>
          </div>
        </section>
      );
    case "products":
    case "menu":
      return (
        <FeatureCard title={type === "menu" ? "おすすめメニュー" : "おすすめ商品"}>
          <div className="igb-products">
            {design.items.slice(0, 2).map((item) => (
              <article key={item.name}>
                <div style={imageStyle} />
                <strong>{item.name}</strong>
                <span>{item.price}</span>
                <button type="button" style={{ background: design.primary }} aria-label="追加">
                  <Plus size={13} />
                </button>
              </article>
            ))}
          </div>
        </FeatureCard>
      );
    case "lookbook":
      return (
        <section className="igb-lookbook">
          <div style={imageStyle}><span>NEW COLLECTION</span></div>
          <div style={{ backgroundImage: `linear-gradient(145deg, ${design.primary}11, ${design.accent}77), url("${design.image}")` }} />
        </section>
      );
    case "flyer":
      return (
        <FeatureCard title="本日の特売">
          <div className="igb-flyer">
            <span>TODAY SALE</span>
            <strong>旬のおすすめが最大30%OFF</strong>
            <small>会員限定価格をチェック</small>
          </div>
        </FeatureCard>
      );
    case "recipe":
      return (
        <FeatureCard title="今日のおすすめレシピ">
          <div className="igb-recipe" style={{ backgroundImage: `linear-gradient(90deg, rgba(0,0,0,.72), transparent), url("${design.image}")` }}>
            <span>15分で完成</span>
            <strong>旬の食材を使った簡単レシピ</strong>
          </div>
        </FeatureCard>
      );
    case "mobileOrder":
      return (
        <section className="igb-row">
          <div className="igb-row__icon"><Coffee size={22} /></div>
          <div>
            <span>MOBILE ORDER</span>
            <strong>待たずに受け取り</strong>
            <small>注文から決済までアプリで完了</small>
          </div>
          <button type="button" style={{ background: design.primary }}>注文する</button>
        </section>
      );
    case "stamp":
      return (
        <FeatureCard title="スタンプカード">
          <div className="igb-stamps">
            <div className="igb-stamps__status">
              <div>
                <span>現在のスタンプ</span>
                <strong>{design.stampCurrent} / {design.stampTotal}</strong>
              </div>
              <Gift size={22} />
            </div>
            <div className="igb-stamps__grid">
              {Array.from({ length: design.stampTotal }).map((_, index) => (
                <span key={index} className={index < design.stampCurrent ? "is-on" : ""} style={index < design.stampCurrent ? { background: design.primary } : undefined}>
                  {index < design.stampCurrent ? <Check size={13} /> : index + 1}
                </span>
              ))}
            </div>
            <small>あと{Math.max(0, design.stampTotal - design.stampCurrent)}個で特典</small>
          </div>
        </FeatureCard>
      );
    case "memberCard":
    case "medicalCard":
      return (
        <section className="igb-pass" style={{ background: `linear-gradient(135deg, ${design.primary}, ${design.accent})` }}>
          <div>
            <span>{type === "medicalCard" ? "DIGITAL PATIENT CARD" : "DIGITAL MEMBERSHIP"}</span>
            <strong>{design.appName}</strong>
          </div>
          {type === "medicalCard" ? <Heart size={22} /> : <Store size={22} />}
          <div className="igb-pass__code">
            <QrCode size={58} />
            <div>
              <span>MEMBER ID</span>
              <strong>{design.memberId}</strong>
              <small>{type === "medicalCard" ? "次回予約 8月25日 10:30" : `${design.memberPoints.toLocaleString()} POINT`}</small>
            </div>
          </div>
        </section>
      );
    case "queue":
      return (
        <section className="igb-card">
          <div className="igb-queue">
            <div>
              <span>現在の待ち状況</span>
              <strong>あと 3 組</strong>
            </div>
            <div>
              <Clock3 size={16} />
              <span>約15分</span>
            </div>
          </div>
          <div className="igb-progress"><span style={{ width: "65%", background: design.primary }} /></div>
          <button type="button" className="igb-full-btn" style={{ background: design.primary }}>順番待ちを申し込む</button>
        </section>
      );
    case "reservation":
      return (
        <FeatureCard title="かんたん予約">
          <div className="igb-dates">
            {["21 木", "22 金", "23 土", "24 日"].map((date, index) => (
              <button key={date} type="button" className={index === 1 ? "is-on" : ""} style={index === 1 ? { background: design.primary } : undefined}>
                <span>8月</span>
                <strong>{date}</strong>
                <small>{index === 2 ? "残りわずか" : "予約可能"}</small>
              </button>
            ))}
          </div>
        </FeatureCard>
      );
    case "staff":
      return (
        <FeatureCard title="スタッフ">
          <div className="igb-staff">
            {["AKI", "MIO", "REN"].map((name, index) => (
              <article key={name}>
                <div style={{ background: `linear-gradient(145deg, ${design.accent}, ${design.primary})` }}>{name.charAt(0)}</div>
                <strong>{name}</strong>
                <span>{index === 0 ? "トップスタイリスト" : "スタイリスト"}</span>
              </article>
            ))}
          </div>
        </FeatureCard>
      );
    case "schedule":
      return (
        <FeatureCard title="今日のレッスン">
          <div className="igb-list">
            {design.items.slice(0, 3).map((item) => (
              <article key={item.name}>
                <time>{item.price}</time>
                <div>
                  <strong>{item.name}</strong>
                  <span>予約可能</span>
                </div>
                <ChevronRight size={15} />
              </article>
            ))}
          </div>
        </FeatureCard>
      );
    case "activity":
      return (
        <section className="igb-card">
          <span className="igb-kicker">WEEKLY ACTIVITY</span>
          <strong className="igb-metric">3,840</strong>
          <small>消費カロリー</small>
          <div className="igb-chart">
            {[40, 72, 55, 85, 48, 95, 64].map((height, index) => (
              <i key={index} style={{ height: `${height}%`, background: index === 5 ? design.primary : `${design.primary}55` }} />
            ))}
          </div>
        </section>
      );
    case "roomKey":
      return (
        <section className="igb-pass igb-pass--center" style={{ background: `linear-gradient(145deg, ${design.primary}, ${design.accent})` }}>
          <Hotel size={24} />
          <span>ROOM</span>
          <strong className="igb-metric">1208</strong>
          <div className="igb-qr"><QrCode size={72} /></div>
          <small>スマートフォンをドアにかざしてください</small>
        </section>
      );
    case "facilityGuide":
      return (
        <FeatureCard title="館内施設">
          <div className="igb-list">
            {[["温泉", "6:00–24:00"], ["レストラン", "17:30–22:00"], ["ラウンジ", "24時間"]].map(([name, hours]) => (
              <article key={name}>
                <MapPin size={17} />
                <div>
                  <strong>{name}</strong>
                  <span>{hours}</span>
                </div>
                <ChevronRight size={15} />
              </article>
            ))}
          </div>
        </FeatureCard>
      );
    case "serviceProgress":
      return (
        <FeatureCard title="ご依頼状況">
          <div className="igb-steps">
            {["受付完了", "作業中", "最終確認", "完了"].map((label, index) => (
              <div key={label} className={index <= 1 ? "is-on" : ""}>
                <span style={index <= 1 ? { background: design.primary } : undefined}>{index < 1 ? <Check size={12} /> : index + 1}</span>
                <strong>{label}</strong>
              </div>
            ))}
          </div>
        </FeatureCard>
      );
    case "petProfile":
      return (
        <section className="igb-row">
          <div className="igb-row__avatar" style={{ background: design.accent }}><PawPrint size={26} /></div>
          <div>
            <span>MY FAMILY</span>
            <strong>モコ</strong>
            <small>トイプードル・3歳</small>
          </div>
          <em>編集</em>
        </section>
      );
    case "vaccination":
      return (
        <FeatureCard title="健康管理">
          <div className="igb-health">
            <Check size={16} />
            <span>狂犬病予防接種</span>
            <strong>接種済み</strong>
          </div>
          <small>次回予定：2027年4月</small>
        </FeatureCard>
      );
    case "ticket":
      return (
        <section className="igb-ticket">
          <div style={{ backgroundImage: `linear-gradient(135deg, ${design.primary}bb, ${design.accent}aa), url("${design.image}")` }}>
            <span>ONE DAY PASS</span>
            <strong>{design.appName}</strong>
          </div>
          <div>
            <QrCode size={64} />
            <div>
              <span>ADMISSION TICKET</span>
              <strong>大人 1名</strong>
              <small>2026.08.20</small>
            </div>
          </div>
        </section>
      );
    case "event":
      return (
        <FeatureCard title="開催中のイベント">
          <div className="igb-events">
            {design.items.slice(0, 2).map((item) => (
              <article key={item.name} style={{ backgroundImage: `linear-gradient(90deg, rgba(10,8,30,.85), transparent), url("${design.image}")` }}>
                <span>{item.price} START</span>
                <strong>{item.name}</strong>
              </article>
            ))}
          </div>
        </FeatureCard>
      );
    case "map":
      return (
        <section className="igb-row">
          <MapIcon size={32} />
          <div>
            <strong>施設マップ</strong>
            <small>現在地から目的地までナビゲーション</small>
          </div>
          <button type="button" style={{ background: design.primary }}>開く</button>
        </section>
      );
    case "coupon":
      return (
        <section className="igb-coupon" style={{ borderColor: design.primary }}>
          <TicketPercent size={22} />
          <div>
            <span>MEMBERS COUPON</span>
            <strong>{design.couponTitle}</strong>
            <small>{design.couponDesc}</small>
          </div>
          <ChevronRight size={16} />
        </section>
      );
    case "news":
      return (
        <FeatureCard title="お知らせ">
          <div className="igb-list">
            {design.news.map((item) => (
              <article key={item.title}>
                <time>{item.date}</time>
                <span>{item.title}</span>
                <ChevronRight size={14} />
              </article>
            ))}
          </div>
        </FeatureCard>
      );
    default:
      return (
        <FeatureCard title={industryBlockLabels[type]}>
          <p className="igb-empty">この機能のプレビューです。</p>
        </FeatureCard>
      );
  }
}

export function IndustryGalleryHome() {
  const { activeStore, customization, activeThemeId } = useAppConfig();
  const template = resolveIndustryGalleryTemplate(activeStore.storeId, customization.appTemplateId);
  const theme = themeMap[customization.themeId ?? ""] ?? themeMap[activeThemeId] ?? themeMap[activeStore.themeId] ?? themeMap["minimal-light"];
  const colors = resolveCustomizationColors(customization, theme);
  const selected = new Set(customization.home.blocks ?? template.blocks);
  const blocks = template.blocks.filter((block) => selected.has(block));
  const design: BlockDesign = {
    appName: customization.brand?.appName?.trim() || template.appName,
    image: customization.brand?.heroImage?.trim() || template.image,
    style: resolveVisualStyle(customization.brand?.visualStyle, template.style),
    primary: colors.primary,
    accent: colors.accent,
    radius: customization.brand?.radius ?? template.radius,
    items: template.items,
    news: template.news,
    stampCurrent: template.stampCurrent,
    stampTotal: template.stampTotal,
    couponTitle: template.couponTitle,
    couponDesc: template.couponDesc,
    memberId: template.memberId,
    memberPoints: template.memberPoints,
  };

  return (
    <div
      className={`ig-home ig-home--${design.style}`}
      style={{ "--ig-radius": `${design.radius}px` } as CSSProperties}
    >
      {(blocks.length > 0 ? blocks : (["hero"] as IndustryBlockType[])).map((block) => (
        <IndustryFeatureBlock key={block} type={block} design={design} />
      ))}
    </div>
  );
}
