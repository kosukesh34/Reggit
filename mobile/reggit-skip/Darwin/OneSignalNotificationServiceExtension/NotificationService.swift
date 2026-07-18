import UserNotifications
import OneSignalExtension

/// お知らせ取消しなど、mutable-content 付き通知を表示前に処理する。
/// 取消し（announcement_cancelled）は届済みを消して、取消し通知自体は表示しない。
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
        let type = additionalDataString(from: userInfo, key: "type") ?? ""
        let announcementId = additionalDataString(from: userInfo, key: "announcement_id") ?? ""
        let originalTitle = additionalDataString(from: userInfo, key: "original_title") ?? ""

        // 取消し、または空/ZWSP だけの通知は表示せず掃除して終了する
        if type == Self.cancelledType || isBlankDisplay(request.content) {
            suppressAndClear(
                request: request,
                announcementId: announcementId,
                originalTitle: originalTitle,
                contentHandler: contentHandler
            )
            return
        }

        guard let bestAttemptContent else {
            contentHandler(request.content)
            return
        }
        OneSignalExtension.didReceiveNotificationExtensionRequest(
            request,
            with: bestAttemptContent,
            withContentHandler: contentHandler
        )
    }

    override func serviceExtensionTimeWillExpire() {
        guard let contentHandler, let bestAttemptContent, let receivedRequest else { return }
        let type = additionalDataString(from: receivedRequest.content.userInfo, key: "type") ?? ""
        if type == Self.cancelledType || isBlankDisplay(bestAttemptContent) {
            finishSuppressed(request: receivedRequest, contentHandler: contentHandler)
            return
        }
        OneSignalExtension.serviceExtensionTimeWillExpireRequest(receivedRequest, with: bestAttemptContent)
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
                if let collapseId, identifier == collapseId || thread == collapseId || identifier.contains(collapseId) {
                    identifiersToRemove.append(identifier)
                    continue
                }
                if !announcementId.isEmpty,
                   self.additionalDataString(from: info, key: "announcement_id") == announcementId {
                    identifiersToRemove.append(identifier)
                    continue
                }
                if !originalTitle.isEmpty, title == originalTitle {
                    identifiersToRemove.append(identifier)
                    continue
                }
                if self.deepContains(info, announcementId: announcementId, collapseId: collapseId ?? "") {
                    identifiersToRemove.append(identifier)
                }
            }

            let uniqueIds = Array(Set(identifiersToRemove))
            // collapse_id で元通知は置換済みになり得るので、先に届済みを消してから contentHandler する
            center.removeDeliveredNotifications(withIdentifiers: uniqueIds)
            self.finishSuppressed(request: request, contentHandler: contentHandler)

            for delay in [0.05, 0.2, 0.5, 1.0] as [TimeInterval] {
                DispatchQueue.main.asyncAfter(deadline: .now() + delay) {
                    center.removeDeliveredNotifications(withIdentifiers: uniqueIds)
                    center.getDeliveredNotifications { remaining in
                        let leftovers = remaining.compactMap { notification -> String? in
                            let id = notification.request.identifier
                            if uniqueIds.contains(id) {
                                return id
                            }
                            if self.isBlankDisplay(notification.request.content) {
                                return id
                            }
                            if !originalTitle.isEmpty, notification.request.content.title == originalTitle {
                                return id
                            }
                            if let collapseId,
                               notification.request.content.threadIdentifier == collapseId
                                || id.contains(collapseId) {
                                return id
                            }
                            return nil
                        }
                        if !leftovers.isEmpty {
                            center.removeDeliveredNotifications(withIdentifiers: leftovers)
                        }
                    }
                }
            }
        }
    }

    /// 空 content はトレイに空白通知が残るため使わない。短い取消し文を渡した直後に同一 ID を削除する。
    private func finishSuppressed(
        request: UNNotificationRequest,
        contentHandler: @escaping (UNNotificationContent) -> Void
    ) {
        let content = UNMutableNotificationContent()
        content.title = request.content.title
        content.body = "このお知らせは取り消されました"
        content.sound = nil
        content.threadIdentifier = request.content.threadIdentifier
        content.userInfo = request.content.userInfo
        if #available(iOS 15.0, *) {
            content.interruptionLevel = .passive
            content.relevanceScore = 0
        }
        contentHandler(content)

        let center = UNUserNotificationCenter.current()
        let ids = [request.identifier, request.content.threadIdentifier].filter { !$0.isEmpty }
        center.removeDeliveredNotifications(withIdentifiers: ids)
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.05) {
            center.removeDeliveredNotifications(withIdentifiers: ids)
        }
    }

    private func isBlankDisplay(_ content: UNNotificationContent) -> Bool {
        isBlankText(content.title) && isBlankText(content.subtitle) && isBlankText(content.body)
    }

    private func isBlankText(_ text: String) -> Bool {
        let trimmed = text
            .replacingOccurrences(of: "\u{200B}", with: "")
            .replacingOccurrences(of: "\u{00A0}", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.isEmpty
    }

    private func additionalDataString(from userInfo: [AnyHashable: Any], key: String) -> String? {
        if let value = stringValueIfPresent(userInfo[key]) {
            return value
        }
        if let additional = additionalDataDictionary(from: userInfo),
           let value = stringValueIfPresent(additional[key]) {
            return value
        }
        return nil
    }

    private func additionalDataDictionary(from userInfo: [AnyHashable: Any]) -> [AnyHashable: Any]? {
        if let custom = userInfo["custom"] as? [AnyHashable: Any] {
            if let additional = custom["a"] as? [AnyHashable: Any] {
                return additional
            }
            if let additionalString = custom["a"] as? String,
               let data = additionalString.data(using: .utf8),
               let additional = (try? JSONSerialization.jsonObject(with: data)) as? [AnyHashable: Any] {
                return additional
            }
        }
        if let customString = userInfo["custom"] as? String,
           let data = customString.data(using: .utf8),
           let custom = (try? JSONSerialization.jsonObject(with: data)) as? [AnyHashable: Any] {
            if let additional = custom["a"] as? [AnyHashable: Any] {
                return additional
            }
            if let additionalString = custom["a"] as? String,
               let additionalData = additionalString.data(using: .utf8),
               let additional = (try? JSONSerialization.jsonObject(with: additionalData)) as? [AnyHashable: Any] {
                return additional
            }
        }
        return nil
    }

    private func deepContains(_ value: Any, announcementId: String, collapseId: String) -> Bool {
        if announcementId.isEmpty, collapseId.isEmpty {
            return false
        }
        if let string = value as? String {
            if !announcementId.isEmpty, string == announcementId {
                return true
            }
            if !collapseId.isEmpty, string == collapseId || string.contains(collapseId) {
                return true
            }
            if !announcementId.isEmpty,
               string.contains("\"announcement_id\":\"\(announcementId)\"")
                || string.contains("\"announcement_id\":\(announcementId)") {
                return true
            }
            if let data = string.data(using: .utf8),
               let json = try? JSONSerialization.jsonObject(with: data) {
                return deepContains(json, announcementId: announcementId, collapseId: collapseId)
            }
            return false
        }
        if let number = value as? NSNumber {
            return !announcementId.isEmpty && number.stringValue == announcementId
        }
        if let dict = value as? [AnyHashable: Any] {
            for (key, nested) in dict {
                if String(describing: key) == "announcement_id",
                   stringValue(nested) == announcementId {
                    return true
                }
                if deepContains(nested, announcementId: announcementId, collapseId: collapseId) {
                    return true
                }
            }
            return false
        }
        if let array = value as? [Any] {
            return array.contains { deepContains($0, announcementId: announcementId, collapseId: collapseId) }
        }
        return false
    }

    private func stringValue(_ value: Any?) -> String {
        if let string = value as? String {
            return string.trimmingCharacters(in: .whitespacesAndNewlines)
        }
        if let number = value as? NSNumber {
            return number.stringValue
        }
        return ""
    }

    private func stringValueIfPresent(_ value: Any?) -> String? {
        let trimmed = stringValue(value)
        return trimmed.isEmpty ? nil : trimmed
    }
}
