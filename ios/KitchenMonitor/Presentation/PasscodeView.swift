import SwiftUI

struct PasscodeView: View {
    let expectedPasscode: String
    let onSuccess: () -> Void
    let onCancel: () -> Void

    @State private var input = ""
    @State private var isError = false

    private let keys = [
        ["1", "2", "3"],
        ["4", "5", "6"],
        ["7", "8", "9"],
        ["", "0", "⌫"],
    ]

    var body: some View {
        VStack(spacing: 24) {
            Image(systemName: "lock.fill")
                .font(.system(size: 36))
                .foregroundStyle(Color(red: 0.12, green: 0.45, blue: 0.38))
            Text("パスコードを入力")
                .font(.title2.bold())
            HStack(spacing: 10) {
                ForEach(0..<max(4, expectedPasscode.count), id: \.self) { index in
                    Circle()
                        .fill(index < input.count ? Color(red: 0.12, green: 0.45, blue: 0.38) : Color.gray.opacity(0.25))
                        .frame(width: 14, height: 14)
                }
            }
            if isError {
                Text("パスコードが違います").foregroundStyle(.red)
            }
            VStack(spacing: 12) {
                ForEach(keys, id: \.self) { row in
                    HStack(spacing: 12) {
                        ForEach(row, id: \.self) { key in
                            Button {
                                handle(key)
                            } label: {
                                Text(key)
                                    .font(.title)
                                    .frame(width: 72, height: 56)
                                    .background(Color.white)
                                    .clipShape(RoundedRectangle(cornerRadius: 12))
                            }
                            .disabled(key.isEmpty)
                            .opacity(key.isEmpty ? 0 : 1)
                        }
                    }
                }
            }
            Button("キャンセル", action: onCancel)
                .padding(.top, 8)
        }
        .padding(32)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(red: 0.96, green: 0.97, blue: 0.96))
    }

    private func handle(_ key: String) {
        if key == "⌫" {
            if !input.isEmpty { input.removeLast() }
            isError = false
            return
        }
        guard input.count < 8 else { return }
        input.append(key)
        if input.count >= expectedPasscode.count {
            if input == expectedPasscode {
                onSuccess()
            } else {
                isError = true
                input = ""
            }
        }
    }
}
