#!/usr/bin/env bash
set -euo pipefail

# macOS self-check of production registration/state/settings sources with local platform/network stubs.
review_repo_root="$(cd "$(dirname "$0")/../.." && pwd)"
review_test_dir="$(mktemp -d "${TMPDIR:-/tmp}/kitchen-push-regression.XXXXXX")"
trap 'rm -rf "$review_test_dir"' EXIT
cd "$review_repo_root"

swiftc -emit-module -emit-library -module-name UIKit tests/native/UIKitStub.swift -o "$review_test_dir/libUIKit.dylib"
{
    printf 'import Combine\n'
    cat ios/KitchenMonitor/Presentation/ViewModels/SettingsViewModel.swift
} > "$review_test_dir/SettingsViewModel.swift"

# This mutation should fail the ordering assertion by bypassing the production registration coordinator.
if [[ "${1:-}" == "--mutation" ]]; then
    sed -i '' 's/PushRegistrationManager.shared.registerDevice(token: token, deviceName: deviceName)/KitchenAPIClient().registerDevice(pushToken: token, deviceName: deviceName)/' "$review_test_dir/SettingsViewModel.swift"
fi

swiftc -swift-version 5 -I "$review_test_dir" -L "$review_test_dir" -lUIKit \
    ios/KitchenMonitor/Services/PushRegistrationManager.swift \
    ios/KitchenMonitor/App/AppState.swift \
    "$review_test_dir/SettingsViewModel.swift" \
    tests/native/KitchenPushRegistrationRegression.swift \
    -o "$review_test_dir/regression"
DYLD_LIBRARY_PATH="$review_test_dir" "$review_test_dir/regression"
