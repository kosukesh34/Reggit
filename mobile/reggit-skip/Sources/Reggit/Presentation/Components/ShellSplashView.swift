import SwiftUI

/// 起動〜WebView 読み込み中に表示する Reggit アイコン付きスプラッシュ。
struct ShellSplashView: View {
    var body: some View {
        ZStack {
            Color.black
                .ignoresSafeArea()

            Image("SplashLogo", bundle: .module)
                .resizable()
                .scaledToFit()
                .frame(maxWidth: 220)
                .accessibilityLabel("Reggit")
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}
