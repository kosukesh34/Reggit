import Foundation

@MainActor
public enum PaymentDeepLinkHandler {
    @discardableResult
    public static func handle(_ url: URL) -> Bool {
        guard url.scheme == NativeAppIdentity.urlScheme, url.host == "payment" else { return false }
        let items = URLComponents(url: url, resolvingAgainstBaseURL: false)?.queryItems ?? []
        let status = items.first(where: { $0.name == "status" })?.value ?? "cancel"
        let orderID = Int(items.first(where: { $0.name == "order_id" })?.value ?? "") ?? 0
        guard orderID > 0 else { return false }
        NativeAppSession.rootViewModel().handlePaymentReturn(orderID: orderID, succeeded: status == "success")
        return true
    }
}
