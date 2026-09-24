import { useEffect, useState } from "react";

type NativeTab = {
  key: string;
  label: string;
  url?: string | null;
};

type NativeConfig = {
  project_name?: string;
  tabs?: NativeTab[];
  home?: {
    menu_layout?: string;
    image_urls?: string[];
  };
};

function pathForTab(key: string): string {
  const paths: Record<string, string> = {
    member: "/membership",
    products: "/menu",
    orders: "/orderHistory",
    coupons: "/coupon",
    stamps: "/stamp",
    reservations: "/reservation",
    announcements: "/news",
    queue: "/queue",
    gallery: "/gallery",
  };
  return paths[key] ?? `/${key}`;
}

function projectKey(): string {
  return typeof window !== "undefined" && window.__PROJECT_KEY__ ? String(window.__PROJECT_KEY__) : "";
}

export function NativeHome() {
  const [config, setConfig] = useState<NativeConfig | null>(null);
  const [index, setIndex] = useState(0);

  useEffect(() => {
    const key = projectKey();
    const url = `/api/v1/app-config?project_key=${encodeURIComponent(key)}`;
    void fetch(url, { headers: { Accept: "application/json" } })
      .then((response) => response.json())
      .then((body: NativeConfig) => setConfig(body))
      .catch(() => setConfig({}));
  }, []);

  const images = config?.home?.image_urls?.filter((url) => url !== "") ?? [];
  const menu = (config?.tabs ?? []).filter((tab) => tab.key !== "home").slice(0, 6);
  const columns = config?.home?.menu_layout === "grid_2_columns" ? "2" : config?.home?.menu_layout === "list" ? "1" : "3";

  useEffect(() => {
    if (images.length < 2) {
      return undefined;
    }
    const timer = window.setInterval(() => {
      setIndex((current) => (current + 1) % images.length);
    }, 5000);
    return () => window.clearInterval(timer);
  }, [images.length]);

  return (
    <div className="skip-home">
      <button
        type="button"
        className="skip-home__hero"
        onClick={() => {
          const image = images[index];
          if (image) {
            window.open(image, "_blank", "noopener,noreferrer");
          }
        }}
      >
        {images.length > 0 ? (
          <img src={images[index]} alt={config?.project_name ?? "ホーム"} />
        ) : (
          <span>{config?.project_name ?? "ホーム"}</span>
        )}
      </button>
      <div className={`skip-home__menu skip-home__menu--${columns}`}>
        {menu.map((tab) => {
          const href = tab.url && tab.url !== "" ? tab.url : `#${pathForTab(tab.key)}`;
          return (
            <a key={tab.key} className="skip-home__item" href={href}>
              <strong>{tab.label}</strong>
            </a>
          );
        })}
      </div>
    </div>
  );
}
