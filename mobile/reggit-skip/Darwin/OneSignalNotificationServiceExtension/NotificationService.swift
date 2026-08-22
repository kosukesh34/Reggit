import UserNotifications

/// お知らせ取消しなど、mutable-content 付き通知を表示前に処理する。
final class NotificationService: UNNotificationServiceExtension {
    private static let cancelledType = "announcement_cancelled"

    private var contentHandler: ((UNNotificationContent) -> Void)?
    private var receivedRequest: UNNotificationRequest?
    private var bestAttemptContent: UNMutableNotificationContent?

    override func didReceive(
        _ request: UNNotificationRequest,
        withContentHandler contentHandler: @escaping (UNNotificationContent) -> Void
    ) {
        self.receivedRequest = request
        self.contentHandler = contentHandler
        self.bestAttemptContent = (request.content.mutableCopy() as? UNMutableNotificationContent)

        let userInfo = request.content.userInfo
        let type = stringValue(from: userInfo, key: "type")
        let announcementId = stringValue(from: userInfo, key: "announcement_id")
        let originalTitle = stringValue(from: userInfo, key: "original_title")

        if type == Self.cancelledType || isBlankDisplay(request.content) {
            suppressAndClear(
                request: request,
                announcementId: announcementId,
                originalTitle: originalTitle,
                contentHandler: contentHandler
            )
            return
        }

        contentHandler(bestAttemptContent ?? request.content)
    }

    override func serviceExtensionTimeWillExpire() {
        guard let contentHandler, let bestAttemptContent, let receivedRequest else { return }
        let type = stringValue(from: receivedRequest.content.userInfo, key: "type")
        if type == Self.cancelledType || isBlankDisplay(bestAttemptContent) {
            contentHandler(UNMutableNotificationContent())
            return
        }
        contentHandler(bestAttemptContent)
    }

    private func suppressAndClear(
        request: UNNotificationRequest,
        announcementId: String,
        originalTitle: String,
        contentHandler: @escaping (UNNotificationContent) -> Void
    ) {
        let collapseId = announcementId.isEmpty ? nil : "reggit:announcement:\(announcementId)"
        var identifiersToRemove: [String] = [request.identifier]
        if let collapseId {
            identifiersToRemove.append(collapseId)
        }

        let center = UNUserNotificationCenter.current()
        center.getDeliveredNotifications { notifications in
            for notification in notifications {
                let info = notification.request.content.userInfo
                let identifier = notification.request.identifier
                let thread = notification.request.content.threadIdentifier
                let title = notification.request.content.title

                if self.isBlankDisplay(notification.request.content) {
                    identifiersToRemove.append(identifier)
                    continue
                }
                if !originalTitle.isEmpty, title == originalTitle {
                    identifiersToRemove.append(identifier)
                }
                if let collapseId, thread == collapseId || identifier == collapseId {
                    identifiersToRemove.append(identifier)
                }
                if !announcementId.isEmpty,
                   self.stringValue(from: info, key: "announcement_id") == announcementId {
                    identifiersToRemove.append(identifier)
                }
            }
            center.removeDeliveredNotifications(withIdentifiers: Array(Set(identifiersToRemove)))
            contentHandler(UNMutableNotificationContent())
        }
    }

    private func isBlankDisplay(_ content: UNNotificationContent) -> Bool {
        let title = content.title.trimmingCharacters(in: .whitespacesAndNewlines)
        let body = content.body.trimmingCharacters(in: .whitespacesAndNewlines)
        return title.isEmpty && body.isEmpty
    }

    private func stringValue(from userInfo: [AnyHashable: Any], key: String) -> String {
        if let value = userInfo[key] as? String {
            return value.trimmingCharacters(in: .whitespacesAndNewlines)
        }
        if let value = userInfo[key] as? NSNumber {
            return value.stringValue
        }
        return ""
    }
}
