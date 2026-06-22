import SwiftUI

struct InvalidURLView: View {
    let urlDescription: String

    var body: some View {
        VStack(spacing: 8) {
            Text("URL設定が不正です")
                .font(.headline)
            Text(urlDescription)
                .font(.caption)
                .foregroundStyle(.secondary)
                .multilineTextAlignment(.center)
        }
    }
}
