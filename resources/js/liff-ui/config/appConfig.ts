import rawConfig from "../mocks/appConfig.json";
import { StoreAppConfig } from "../types/app";
import { additionalStores } from "./additionalStores";

export interface AppConfigPayload {
  stores: StoreAppConfig[];
}

export const appConfig: AppConfigPayload = {
  stores: [...(rawConfig as AppConfigPayload).stores, ...additionalStores],
};
