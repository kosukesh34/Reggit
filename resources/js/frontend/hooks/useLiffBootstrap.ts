import { useLiffHashSync } from '../context/FrontendAppContext';
import '../../shared/utils/liffInit';

export function useLiffBootstrap(): void {
    useLiffHashSync();
}

export function LiffBootstrap(): null {
    useLiffBootstrap();
    return null;
}
