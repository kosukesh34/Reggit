import Foundation

/// Darwin（PassKit）側が起動時に登録する。共有モジュールから Wallet 追加を呼ぶ橋渡し。
@MainActor
public enum NativeWalletBridge {
    public static var addAppleWalletPass: ((String) -> Void)?

    public static var canAddAppleWalletPass: Bool {
        addAppleWalletPass != nil
    }
}
