import SwiftUI

/* SKIP @bridge */public struct ReggitRootView : View {
    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        ContentView(viewModel: MobileWebShellFactory.makeViewModel())
            .task {
                logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
            }
    }
}
