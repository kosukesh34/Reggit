import SwiftUI

struct MembershipRegistrationGateView: View {
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
            onSkip: canSkip ? onSkip : nil
        )
    }
}
