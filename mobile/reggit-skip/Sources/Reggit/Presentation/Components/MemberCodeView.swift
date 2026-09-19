import SkipFuse
import SwiftUI

/// 会員コード表示。Code128 は純 Swift 実装で iOS/Android 共通。
struct MemberCodeView: View {
    let value: String
    var codeType: String = "barcode"
    var height: CGFloat = 72

    var body: some View {
        Group {
            if value.isEmpty {
                EmptyView()
            } else if codeType == "qrcode" {
                // Skip 両対応のため CoreImage に依存せず、番号を大きく＋バーコードで提示
                VStack(spacing: 8) {
                    Text(value)
                        .font(.system(size: 18, weight: .bold, design: .monospaced))
                        .tracking(1)
                    Code128BarcodeView(value: value)
                        .frame(height: height * 0.7)
                }
            } else {
                Code128BarcodeView(value: value)
                    .frame(height: height)
            }
        }
    }
}

struct Code128BarcodeView: View {
    let value: String

    var body: some View {
        let modules = Code128Encoder.encode(value)
        GeometryReader { geo in
            let moduleWidth = max(geo.size.width / CGFloat(max(modules.count, 1)), 1)
            HStack(spacing: 0) {
                ForEach(Array(modules.enumerated()), id: \.offset) { _, isBar in
                    Rectangle()
                        .fill(isBar ? Color.black : Color.white)
                        .frame(width: moduleWidth)
                }
            }
        }
        .background(Color.white)
    }
}

enum Code128Encoder {
    private static let patterns: [String] = [
        "11011001100", "11001101100", "11001100110", "10010011000", "10010001100",
        "10001001100", "10011001000", "10011000100", "10001100100", "11001001000",
        "11001000100", "11000100100", "10110011100", "10011011100", "10011001110",
        "10111001100", "10011101100", "10011100110", "11001110010", "11001011100",
        "11001001110", "11011100100", "11001110100", "11101101110", "11101001100",
        "11100101100", "11100100110", "11101100100", "11100110100", "11100110010",
        "11011011000", "11011000110", "11000110110", "10100011000", "10001011000",
        "10001000110", "10110001000", "10001101000", "10001100010", "11010001000",
        "11000101000", "11000100010", "10110111000", "10110001110", "10001101110",
        "10111011000", "10111000110", "10001110110", "11101110110", "11010001110",
        "11000101110", "11011101000", "11011100010", "11011101110", "11101011000",
        "11101000110", "11100010110", "11101101000", "11101100010", "11100011010",
        "11101111010", "11001000010", "11110001010", "10100110000", "10100001100",
        "10010110000", "10010000110", "10000101100", "10000100110", "10110010000",
        "10110000100", "10011010000", "10011000010", "10000110100", "10000110010",
        "11000010010", "11001010000", "11110111010", "11000010100", "10001111010",
        "10100111100", "10010111100", "10010011110", "10111100100", "10011110100",
        "10011110010", "11110100100", "11110010100", "11110010010", "11011011110",
        "11011110110", "11110110110", "10101111000", "10100011110", "10001011110",
        "10111101000", "10111100010", "11110101000", "11110100010", "10111011110",
        "10111101110", "11101011110", "11110101110", "11010000100", "11010010000",
        "11010011100", "1100011101011",
    ]

    private static let startB = 104
    private static let stop = 106

    static func encode(_ raw: String) -> [Bool] {
        let sanitized = raw.filter { $0.unicodeScalars.first.map { $0.value >= 32 && $0.value <= 126 } ?? false }
        let text = sanitized.isEmpty ? raw : sanitized
        var codes: [Int] = [startB]
        var checksum = startB
        for (index, char) in text.enumerated() {
            let value = Int(char.unicodeScalars.first?.value ?? 32) - 32
            let code = max(0, min(94, value))
            codes.append(code)
            checksum += code * (index + 1)
        }
        codes.append(checksum % 103)
        codes.append(stop)

        var modules: [Bool] = []
        for code in codes {
            let pattern = patterns[min(code, patterns.count - 1)]
            for bit in pattern {
                modules.append(bit == "1")
            }
        }
        return modules
    }
}

struct OrderProgressTracker: View {
    let status: String
    @Environment(\.brandAccent) var accent

    var body: some View {
        let current = OrderProgress.index(for: status)
        VStack(alignment: .leading, spacing: 14) {
            ForEach(Array(OrderProgress.steps.enumerated()), id: \.offset) { index, step in
                HStack(alignment: .top, spacing: 12) {
                    VStack(spacing: 0) {
                        Circle()
                            .fill(index <= current ? accent : AppDesign.hairline)
                            .frame(width: 14, height: 14)
                        if index < OrderProgress.steps.count - 1 {
                            Rectangle()
                                .fill(index < current ? accent : AppDesign.hairline)
                                .frame(width: 2, height: 28)
                        }
                    }
                    VStack(alignment: .leading, spacing: 2) {
                        Text(step.label)
                            .font(.system(size: 15, weight: index == current ? .bold : .semibold))
                            .foregroundStyle(index <= current ? AppDesign.ink : AppDesign.inkSecondary)
                        Text(step.detail)
                            .font(.system(size: 12))
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                    Spacer(minLength: 0)
                    if index == current && status != "cancelled" {
                        Text("現在")
                            .font(.system(size: 11, weight: .bold))
                            .foregroundStyle(accent)
                            .padding(.horizontal, 8)
                            .padding(.vertical, 4)
                            .background(accent.opacity(0.12))
                            .clipShape(Capsule())
                    }
                }
            }
        }
    }
}
