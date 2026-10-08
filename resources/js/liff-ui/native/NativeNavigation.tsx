import { createContext, useCallback, useContext, useEffect, useRef, useState, type PropsWithChildren } from "react";
type Navigation = { back?: () => void; setBack: (action?: () => void) => void };
const Context = createContext<Navigation>({ setBack: () => {} });
export function NativeNavigationProvider({ children }: PropsWithChildren) {
  const [back, updateBack] = useState<(() => void)>();
  const setBack = useCallback((action?: () => void) => updateBack(() => action), []);
  return <Context.Provider value={{ back, setBack }}>{children}</Context.Provider>;
}
export const useNativeNavigation = () => useContext(Context);
export function useNativeBack(enabled: boolean, action: () => void) {
  const { setBack } = useNativeNavigation();
  const actionRef = useRef(action); actionRef.current = action;
  useEffect(() => {
    if (!enabled) return;
    setBack(() => actionRef.current());
    return () => setBack(undefined);
  }, [enabled, setBack]);
}
