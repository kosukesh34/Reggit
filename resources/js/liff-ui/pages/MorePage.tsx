import { Link } from "react-router-dom";
import { useNativeConfig, nativeTabPath, nativeTabIconClass } from "../native/nativeConfig";
export function MorePage() {
  const { config } = useNativeConfig();
  const tabs = config.navigation?.hamburger ?? config.tabs ?? [];
  return <nav className="native-screen native-more" aria-label="メニュー">{tabs.map((tab) => <Link className="native-more-row" key={tab.key} to={nativeTabPath(tab.key)}><i className={nativeTabIconClass(tab)} /><span>{tab.label}</span><i className="fa-solid fa-chevron-right" /></Link>)}<Link className="native-more-row" to="/more/link"><i className="fa-solid fa-link" /><span>既存会員連携</span><i className="fa-solid fa-chevron-right" /></Link>
    <Link className="native-more-row" to="/more/transfer">引き継ぎ</Link><Link className="native-more-row" to="/more/withdraw">退会</Link></nav>;
}
