import rawConfig from "../mocks/appConfig.json";
import { StoreAppConfig } from "../types/app";
import { additionalStores } from "./additionalStores";

export interface AppConfigPayload {
  stores: StoreAppConfig[];
}

export const appConfig: AppConfigPayload = {
  stores: [...(rawConfig as AppConfigPayload).stores, ...additionalStores],
};

const configuredStoreId = import.meta.env.VITE_REGGIT_STORE_ID?.trim();
export const defaultStoreId = appConfig.stores.some((store) => store.storeId === configuredStoreId)
  ? configuredStoreId as string
  : appConfig.stores[0]?.storeId ?? "store_cafe";
