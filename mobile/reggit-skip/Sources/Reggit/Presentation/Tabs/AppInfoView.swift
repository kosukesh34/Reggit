import Foundation
import SkipFuse
import SwiftUI

struct AppInfoView: View {
    let userId: Int?
    let displayedAt: Date
    let onClose: () -> Void

    private var device: DefaultNativeDeviceContextProvider { DefaultNativeDeviceContextProvider() }

    private var platformName: String {
        switch device.platform {
        case .ios: return "iOS"
        case .android: return "Android"
        case .unknown: return "不明"
        }
    }

    private var displayedAtText: String {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "ja_JP")
        formatter.timeZone = PlatformRuntime.deviceTimeZone
        formatter.dateFormat = "yyyy/MM/dd HH:mm:ss zzz"
        return formatter.string(from: displayedAt)
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 0) {
                    infoRow("端末", value: PlatformRuntime.deviceModel)
                    infoRow("端末ID", value: device.deviceIdentifier)
                    infoRow("アプリバージョン", value: device.appVersion)
                    infoRow("OSバージョン", value: "\(platformName) \(device.osVersion)")
                    infoRow("ユーザーID", value: userId.map(String.init) ?? "未取得")
                    infoRow("表示時刻", value: displayedAtText)
                }
                .padding(16)
                .background(AppDesign.surface)
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
                .padding(16)
            }
            .background(AppDesign.canvas.ignoresSafeArea())
            .navigationTitle("アプリ情報")
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    Button("閉じる", action: onClose)
                }
            }
        }
    }

    private func infoRow(_ title: String, value: String) -> some View {
        HStack(alignment: .top, spacing: 12) {
            Text(title)
                .font(.subheadline)
                .foregroundStyle(AppDesign.inkSecondary)
                .frame(width: 120, alignment: .leading)
            Text(value)
                .font(.subheadline)
                .foregroundStyle(AppDesign.ink)
                .frame(maxWidth: .infinity, alignment: .leading)
        }
        .padding(.vertical, 12)
    }
}
