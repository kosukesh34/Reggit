import SwiftUI

/// 引き継ぎ用 QR。Skip は CoreImage を使えないため、Version 2 / 誤り訂正 L / バイトモードだけ描く。
/// ponytail: 32 バイトまで。長い文字列は nil。マスクは 0 固定。
struct QRCodeView: View {
    let value: String

    var body: some View {
        let modules = QRCodeMatrix.modules(for: value) ?? []
        GeometryReader { geo in
            let count = max(modules.count, 1)
            let cell = geo.size.width / CGFloat(count + 8)
            VStack(spacing: 0) {
                Spacer(minLength: 0)
                ForEach(0..<modules.count, id: \.self) { row in
                    HStack(spacing: 0) {
                        ForEach(0..<modules[row].count, id: \.self) { column in
                            Rectangle()
                                .fill(modules[row][column] ? Color.black : Color.white)
                                .frame(width: cell, height: cell)
                        }
                    }
                }
                Spacer(minLength: 0)
            }
            .frame(width: geo.size.width, height: geo.size.height)
        }
        .aspectRatio(1, contentMode: .fit)
        .background(Color.white)
    }
}

enum QRCodeMatrix {
    static func modules(for text: String) -> [[Bool]]? {
        let bytes = Array(text.utf8)
        guard bytes.count <= 32 else { return nil }
        let data = paddedData(bytes)
        let codewords = data + reedSolomon(data, errorCount: 10)
        return place(codewords, size: 25)
    }

    private static func paddedData(_ bytes: [UInt8]) -> [UInt8] {
        var bits = "0100"
        let length = String(bytes.count, radix: 2)
        bits += String(repeating: "0", count: max(0, 8 - length.count)) + length
        for byte in bytes {
            let raw = String(byte, radix: 2)
            bits += String(repeating: "0", count: max(0, 8 - raw.count)) + raw
        }
        bits += "0000"
        while bits.count % 8 != 0 { bits += "0" }
        var words: [UInt8] = []
        var index = bits.startIndex
        while index < bits.endIndex {
            let next = bits.index(index, offsetBy: 8, limitedBy: bits.endIndex) ?? bits.endIndex
            words.append(UInt8(bits[index..<next], radix: 2) ?? 0)
            index = next
        }
        var pad = 0
        while words.count < 34 {
            words.append(pad % 2 == 0 ? 0xEC : 0x11)
            pad += 1
        }
        return Array(words.prefix(34))
    }

    private static func reedSolomon(_ data: [UInt8], errorCount: Int) -> [UInt8] {
        let exp = gfExp()
        let log = gfLog(exp)
        var generator = [UInt8(1)]
        for i in 0..<errorCount {
            generator = multiply(generator, [1, exp[i]], exp: exp, log: log)
        }
        var remainder = data + [UInt8](repeating: 0, count: errorCount)
        for i in 0..<data.count {
            let factor = remainder[i]
            if factor == 0 { continue }
            for j in 0..<generator.count {
                remainder[i + j] ^= multiplyByte(generator[j], factor, exp: exp, log: log)
            }
        }
        return Array(remainder.suffix(errorCount))
    }

    private static func gfExp() -> [UInt8] {
        var exp = [UInt8](repeating: 0, count: 512)
        var value = 1
        for i in 0..<255 {
            exp[i] = UInt8(value)
            value <<= 1
            if value >= 256 { value ^= 0x11D }
        }
        for i in 255..<512 { exp[i] = exp[i - 255] }
        return exp
    }

    private static func gfLog(_ exp: [UInt8]) -> [Int] {
        var log = [Int](repeating: 0, count: 256)
        for i in 0..<255 { log[Int(exp[i])] = i }
        return log
    }

    private static func multiply(_ left: [UInt8], _ right: [UInt8], exp: [UInt8], log: [Int]) -> [UInt8] {
        var product = [UInt8](repeating: 0, count: left.count + right.count - 1)
        for i in 0..<left.count {
            for j in 0..<right.count {
                product[i + j] ^= multiplyByte(left[i], right[j], exp: exp, log: log)
            }
        }
        return product
    }

    private static func multiplyByte(_ left: UInt8, _ right: UInt8, exp: [UInt8], log: [Int]) -> UInt8 {
        if left == 0 || right == 0 { return 0 }
        return exp[log[Int(left)] + log[Int(right)]]
    }

    private static func place(_ codewords: [UInt8], size: Int) -> [[Bool]] {
        var modules = Array(repeating: Array(repeating: false, count: size), count: size)
        var reserved = Array(repeating: Array(repeating: false, count: size), count: size)
        func mark(_ row: Int, _ column: Int, _ dark: Bool) {
            guard row >= 0, column >= 0, row < size, column < size else { return }
            modules[row][column] = dark
            reserved[row][column] = true
        }
        func finder(_ row: Int, _ column: Int) {
            for y in 0..<7 {
                for x in 0..<7 {
                    let edge = y == 0 || y == 6 || x == 0 || x == 6
                    let core = y >= 2 && y <= 4 && x >= 2 && x <= 4
                    mark(row + y, column + x, edge || core)
                }
            }
            for i in -1...7 {
                mark(row - 1, column + i, false)
                mark(row + 7, column + i, false)
                mark(row + i, column - 1, false)
                mark(row + i, column + 7, false)
            }
        }
        finder(0, 0)
        finder(0, size - 7)
        finder(size - 7, 0)
        for i in 8..<(size - 8) {
            mark(6, i, i % 2 == 0)
            mark(i, 6, i % 2 == 0)
        }
        let center = 18
        for y in -2...2 {
            for x in -2...2 {
                let dark = max(abs(x), abs(y)) != 1
                mark(center + y, center + x, dark)
            }
        }
        mark(4 * 2 + 9, 8, true)
        let format = "111011111000100"
        let formatBits = Array(format)
        let positions = [(8, 0), (8, 1), (8, 2), (8, 3), (8, 4), (8, 5), (8, 7), (8, 8), (7, 8), (5, 8), (4, 8), (3, 8), (2, 8), (1, 8), (0, 8)]
        for (index, bit) in formatBits.enumerated() {
            let dark = bit == "1"
            mark(positions[index].0, positions[index].1, dark)
            let row = index < 7 ? size - 1 - index : 8
            let column = index < 7 ? 8 : size - 15 + index
            mark(row, column, dark)
        }
        var bitIndex = 0
        let bits = codewords.flatMap { byte -> [Bool] in
            (0..<8).reversed().map { ((byte >> $0) & 1) == 1 }
        }
        var column = size - 1
        while column > 0 {
            if column == 6 { column -= 1 }
            for vertical in 0..<size {
                for offset in 0..<2 {
                    let x = column - offset
                    let upward = ((column + 1) & 2) == 0
                    let y = upward ? size - 1 - vertical : vertical
                    if reserved[y][x] { continue }
                    let dark = bitIndex < bits.count && bits[bitIndex]
                    let masked = (y + x) % 2 == 0 ? !dark : dark
                    modules[y][x] = masked
                    bitIndex += 1
                }
            }
            column -= 2
        }
        return modules
    }
}
