import Foundation
import Observation
import SwiftUI

/* SKIP @bridge */public struct ReggitRootView : View {
    @State var needsProjectSelection: Bool? = nil

    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        Group {
            if needsProjectSelection == true {
                MobileProjectSelectionView {
                    MobileWebShellSession.reset()
                    needsProjectSelection = false
                }
            } else if needsProjectSelection == false {
                ContentView(viewModel: MobileWebShellSession.viewModel())
            } else {
                ProgressView()
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            }
        }
        .task {
            let bundleKey = BundleMobileWebConfigRepository().resolveConfig().projectKey
            needsProjectSelection = MobileSelectedProjectStore.needsSelection(bundleDefaultKey: bundleKey)
        }
        .onAppear {
            logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
        }
    }
}
