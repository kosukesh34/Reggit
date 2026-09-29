import Foundation
import Observation
import SwiftUI
import SkipFuse

@Observable
final class HeaderNavigationState {
    var backAction: (() -> Void)?
    var owner: UUID?
}

private struct HeaderNavigationKey: EnvironmentKey {
    static var defaultValue: HeaderNavigationState { HeaderNavigationState() }
}

extension EnvironmentValues {
    var headerNavigation: HeaderNavigationState {
        get { self[HeaderNavigationKey.self] }
        set { self[HeaderNavigationKey.self] = newValue }
    }
}

struct HeaderBackModifier: ViewModifier {
    @Environment(\.dismiss) var dismiss
    @Environment(\.headerNavigation) var navigation
    @State var owner = UUID()

    func body(content: Content) -> some View {
        content
            .navigationBarBackButtonHidden(true)
            .onAppear {
                navigation.owner = owner
                navigation.backAction = { dismiss() }
            }
            .onDisappear {
                if navigation.owner == owner {
                    navigation.backAction = nil
                    navigation.owner = nil
                }
            }
    }
}

extension View {
    func headerBackDestination() -> some View {
        modifier(HeaderBackModifier())
    }
}
