import SwiftUI

struct MembershipRegistrationGateView: View {
    let projectName: String
    let message: String?
    let canSkip: Bool
    let isRegistering: Bool
    let errorMessage: String?
    let fields: [ProfileFieldDefinition]
    let initialValues: [String: String]
    let initialListValues: [String: [String]]
    let serverFieldErrors: [String: String]
    let onSubmit: ([String: String], [String: [String]]) -> Void
    let onSkip: () -> Void

    var body: some View {
        ProfileEditView(
            fields: fields,
            initialValues: initialValues,
            initialListValues: initialListValues,
            isSaving: isRegistering,
            errorMessage: errorMessage,
            serverFieldErrors: serverFieldErrors,
            onSave: onSubmit,
            isRegistration: true,
            introduction: "\(projectName)\n\(displayMessage)",
            onSkip: canSkip ? onSkip : nil
        )
    }

    private var displayMessage: String {
        if let message, !message.isEmpty { return message }
        return canSkip
            ? "会員登録でポイントやクーポンが使えます。"
            : "ご利用には会員登録が必要です。"
    }
}
