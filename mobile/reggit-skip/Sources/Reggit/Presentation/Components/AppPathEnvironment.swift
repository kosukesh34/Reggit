import SwiftUI

private struct OpenAppPathKey: EnvironmentKey {
    static var defaultValue: @MainActor (String) -> Void { { _ in } }
}

extension EnvironmentValues {
    /// アプリ内パス（/survey?survey=1・/coupon など）を開く。画面の切り替えを持つ root が設定する
    var openAppPath: @MainActor (String) -> Void {
        get { self[OpenAppPathKey.self] }
        set { self[OpenAppPathKey.self] = newValue }
    }
}

/// アンケートのシートを開く対象。番号がなければ一覧
struct SurveyRoute: Identifiable, Equatable {
    let id = UUID()
    let surveyID: Int?
}
