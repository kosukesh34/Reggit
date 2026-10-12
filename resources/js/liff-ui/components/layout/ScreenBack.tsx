import { Link, useLocation } from "react-router-dom";
import { useNativeNavigation } from "../../native/NativeNavigation";
import { screenBack } from "../../native/screenBack";

export function ScreenBack() {
  const { pathname } = useLocation();
  const { back } = useNativeNavigation();
  const parent = screenBack(pathname);
  if (!back && !parent) return null;
  const content = <><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="m12 5-7 7 7 7M5 12h14" /></svg><span>{back ? "前の画面へ戻る" : parent?.label}</span></>;
  return <nav className="web-page-navigation native-page-navigation" aria-label="ページの移動">
    {back ? <button className="web-back" type="button" onClick={back}>{content}</button> : parent ? <Link className="web-back" to={parent.to}>{content}</Link> : null}
  </nav>;
}
