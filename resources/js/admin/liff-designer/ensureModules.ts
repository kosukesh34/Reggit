export type LiffDesignerModulesRegistry = Record<string, any>;

export function ensureLiffDesignerModules(): LiffDesignerModulesRegistry {
    if (window.LiffDesignerModules === undefined) {
        window.LiffDesignerModules = {};
    }
    return window.LiffDesignerModules;
}
