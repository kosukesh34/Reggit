import { useEffect, useState } from "react";
import { CenterPopup } from "../ui/CenterPopup";
import { Button } from "../ui/Button";
import { openRegister, startLineLogin } from "../../../frontend/features/member/api";
import { isMembershipRequired } from "../../../frontend/utils/membershipPolicy";

export function LoginPromptPopup(): JSX.Element {
  const [open, setOpen] = useState(false);
  const [forced, setForced] = useState(false);
  const hasLine = typeof window !== "undefined" && Boolean(window.__LINE_USER_ID__);

  useEffect(() => {
    const onOpen = (event: Event): void => {
      const detail = (event as CustomEvent<{ forced?: boolean }>).detail;
      setForced(detail?.forced === true || isMembershipRequired());
      setOpen(true);
    };
    document.addEventListener("liff-member-open-login-popup", onOpen);
    return () => {
      document.removeEventListener("liff-member-open-login-popup", onOpen);
    };
  }, []);

  const dismiss = (): void => {
    if (forced) {
      return;
    }
    try {
      window.sessionStorage.setItem("member_registration_prompt_dismissed", "1");
    } catch {
      /* ignore */
    }
    setOpen(false);
  };

  return (
    <CenterPopup open={open} onClose={dismiss} title={hasLine ? "会員パス" : "ログイン"}>
      <p>
        {hasLine
          ? "発行すると、ポイントやクーポンを店舗で使えます。"
          : "LINEでログインすると、会員パスをご利用いただけます。"}
      </p>
      <div className="stack stack--sm">
        <Button
          variant="solid"
          onClick={() => {
            setOpen(false);
            if (hasLine) {
              openRegister(forced);
              return;
            }
            startLineLogin();
          }}
        >
          {hasLine ? "会員パスを発行" : "LINEでログイン"}
        </Button>
        {forced ? null : (
          <Button variant="outline" onClick={dismiss}>あとで</Button>
        )}
      </div>
    </CenterPopup>
  );
}
