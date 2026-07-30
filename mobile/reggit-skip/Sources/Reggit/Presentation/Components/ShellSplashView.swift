import SwiftUI

/// 起動〜WebView 読み込み中に表示する Reggit アイコン付きスプラッシュ。
struct ShellSplashView: View {
    var showsActivityIndicator = false

    var body: some View {
        GeometryReader { geometry in
            let logoSide = logoSideLength(in: geometry.size)
            ZStack {
                Color.black
                    .ignoresSafeArea()

                VStack(spacing: 24) {
                    Image("SplashLogo", bundle: .module)
                        .resizable()
                        .scaledToFit()
                        .frame(width: logoSide, height: logoSide)
                        .accessibilityLabel("Reggit")

                    if showsActivityIndicator {
                        ProgressView()
                            .progressViewStyle(.circular)
                            .tint(.white)
                            .accessibilityLabel("読み込み中")
                    }
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }

    /// 画面幅・高さの比率で抑え、端末差でアイコンが肥大しないようにする。
    private func logoSideLength(in size: CGSize) -> CGFloat {
        let byWidth = size.width * 0.42
        let byHeight = size.height * 0.22
        return min(byWidth, byHeight, 200)
    }
}
