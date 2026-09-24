import SwiftUI

@MainActor
@Observable
final class ExistingMemberLinkModel {
    var form: ExistingMemberLinkForm?
    var values: [String: String] = [:]
    var message: String?
    var errorMessage: String?
    var isBusy = false

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    func load() async {
        isBusy = true
        errorMessage = nil
        defer { isBusy = false }
        do {
            let loaded = try await apiClient.fetchExistingMemberLinkForm()
            form = loaded
            for field in loaded.fields where values[field.key] == nil {
                values[field.key] = ""
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func submit() async -> Bool {
        guard let form, form.isEnabled else {
            errorMessage = "既存会員連携は利用できません"
            return false
        }
        isBusy = true
        errorMessage = nil
        message = nil
        defer { isBusy = false }
        do {
            let result = try await apiClient.submitExistingMemberLink(values: values)
            message = result.message
            return result.linked
        } catch {
            errorMessage = error.localizedDescription
            return false
        }
    }
}

struct ExistingMemberLinkView: View {
    let onLinked: () -> Void
    let onClose: () -> Void
    @State var model: ExistingMemberLinkModel
    @Environment(\.brandAccent) var accent

    init(apiClient: any ReggitAPIServing, onLinked: @escaping () -> Void, onClose: @escaping () -> Void) {
        self.onLinked = onLinked
        self.onClose = onClose
        _model = State(initialValue: ExistingMemberLinkModel(apiClient: apiClient))
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    if let description = model.form?.description, !description.isEmpty {
                        Text(description)
                            .font(.subheadline)
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                    if model.form?.isEnabled == false {
                        Text("この店舗では既存会員連携を使っていません")
                            .foregroundStyle(AppDesign.ink)
                    }
                    if let form = model.form {
                        ForEach(form.fields) { field in
                            VStack(alignment: .leading, spacing: 6) {
                                Text(field.label)
                                    .font(.subheadline.weight(.semibold))
                                TextField(field.label, text: binding(for: field.key))
                                    .textFieldStyle(.roundedBorder)
                                    .keyboardType(field.key == "phone" ? .phonePad : (field.key == "email" ? .emailAddress : .default))
                            }
                        }
                    }
                    if let message = model.message {
                        Text(message).foregroundStyle(accent)
                    }
                    if let errorMessage = model.errorMessage {
                        Text(errorMessage).foregroundStyle(.red)
                    }
                    Button {
                        Task {
                            if await model.submit() {
                                onLinked()
                            }
                        }
                    } label: {
                        Text(model.isBusy ? "確認中" : "連携する")
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 12)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(accent)
                    .disabled(model.isBusy || model.form?.isEnabled != true)
                }
                .padding(16)
            }
            .background(AppDesign.canvas.ignoresSafeArea())
            .navigationTitle("既存会員連携")
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    Button("閉じる", action: onClose)
                }
            }
        }
        .task { await model.load() }
    }

    private func binding(for key: String) -> Binding<String> {
        Binding(
            get: { model.values[key] ?? "" },
            set: { model.values[key] = $0 }
        )
    }
}
