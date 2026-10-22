import React from "react";
import { createRoot } from "react-dom/client";
import { MemoryRouter } from "react-router-dom";
import "../../css/shop.css";
import { AppConfigProvider } from "../liff-ui/context/AppConfigContext";
import { RuntimeDataProvider } from "../liff-ui/context/RuntimeDataContext";
import { NativeConfigProvider } from "../liff-ui/native/nativeConfig";
import { ShopStorefront } from "./ShopStorefront";

const root = document.getElementById("shop-app-root");
if (root !== null) {
  createRoot(root).render(
    <React.StrictMode>
      <MemoryRouter>
        <AppConfigProvider>
          <NativeConfigProvider>
            <RuntimeDataProvider>
              <ShopStorefront />
            </RuntimeDataProvider>
          </NativeConfigProvider>
        </AppConfigProvider>
      </MemoryRouter>
    </React.StrictMode>,
  );
}
