import SwiftUI

struct ShellLoadingOverlay: View {
    var body: some View {
        VStack(spacing: 12) {
            ProgressView()
            Text("読み込み中...")
                .font(.subheadline)
                .foregroundStyle(.secondary)
        }
        .padding(20)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 14))
    }
}
