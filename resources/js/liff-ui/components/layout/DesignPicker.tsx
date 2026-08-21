import { Check, ChevronRight } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { designerTemplates, templatesByGroup } from "../../config/appTemplates";
import { useAppConfig } from "../../context/AppConfigContext";
import { BottomSheet } from "../ui/BottomSheet";

export const OPEN_DESIGN_PICKER_EVENT = "reggit:open-design-picker";

export function openDesignPicker(): void {
  window.dispatchEvent(new Event(OPEN_DESIGN_PICKER_EVENT));
}

export function DesignShowcaseBanner() {
  const { stores, activeStore } = useAppConfig();
  if (stores.length <= 1 || window.__IS_DESIGNER_EMBED_PREVIEW__ === true) {
    return null;
  }

  return (
    <button className="design-showcase-banner" type="button" onClick={openDesignPicker}>
      <span>
        <small>デザインを選ぶ</small>
        <strong>{activeStore.storeName}</strong>
      </span>
      <ChevronRight size={16} />
    </button>
  );
}

interface DesignPickerProps {
  open: boolean;
  onClose: () => void;
}

export function DesignPicker({ open, onClose }: DesignPickerProps) {
  const { stores, activeStore, setActiveStoreId } = useAppConfig();
  const navigate = useNavigate();
  const groups = templatesByGroup(designerTemplates(stores));

  return (
    <BottomSheet open={open} onClose={onClose} title="デザインを選ぶ">
      <div className="design-picker">
        {groups.map((group) => (
          <section className="design-picker__group" key={group.group}>
            <h2>{group.label}</h2>
            <div className="design-picker__grid">
              {group.items.map((item) => {
                const selected = item.store.storeId === activeStore.storeId;
                const photo = item.store.home.heroImage;
                return (
                  <button
                    className={selected ? "design-card is-active" : "design-card"}
                    type="button"
                    key={item.store.storeId}
                    aria-pressed={selected}
                    onClick={() => {
                      setActiveStoreId(item.store.storeId);
                      onClose();
                      navigate("/home");
                    }}
                  >
                    <span
                      className={`design-card__photo${photo === "" ? " design-card__photo--empty" : ""}`}
                      style={photo !== "" ? { backgroundImage: `url("${photo}")` } : undefined}
                    >
                      {selected ? <i><Check size={14} /></i> : null}
                    </span>
                    <strong>{item.store.storeName}</strong>
                    <small>{item.description}</small>
                  </button>
                );
              })}
            </div>
          </section>
        ))}
      </div>
    </BottomSheet>
  );
}
