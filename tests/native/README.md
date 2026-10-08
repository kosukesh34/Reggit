# Kitchen push registration self-check

Run locally on macOS with the installed Swift toolchain:

```sh
bash tests/native/run-kitchen-push-registration.sh
```

The script compiles the production `PushRegistrationManager`, `AppState`, and `SettingsViewModel` sources. The temporary settings source only adds its missing explicit Combine import for standalone compilation. UIKit, credentials, settings storage, and HTTP are local stubs; no live API, Firebase, Keychain, or production UserDefaults keys are touched. Temporary binaries are removed on exit. These files are outside the app's Xcode source directory and are not added to CI.

Assertions cover repeated registration with the same token, settings success/error messages, persistence only after success, an in-flight settings registration completing before logout unregistration, rejecting registration during logout, resuming after login, and clearing local credentials when unregistration fails offline.

The mutation command deliberately bypasses the shared coordinator for settings registration. It must exit nonzero at the ordering assertion:

```sh
bash tests/native/run-kitchen-push-registration.sh --mutation
```

This self-check does not verify Firebase callbacks, APNs delivery, UIKit rendering, or live HTTP timing. Use the separate Xcode simulator build for production framework/type integration and a physical device for end-to-end push delivery.
