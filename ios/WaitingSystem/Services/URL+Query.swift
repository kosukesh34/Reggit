import Foundation

extension URL {
    func appendingQueryItems(_ items: [URLQueryItem]) -> URL {
        guard var components = URLComponents(url: self, resolvingAgainstBaseURL: false) else {
            return self
        }
        var queryItems = components.queryItems ?? []
        queryItems.append(contentsOf: items)
        components.queryItems = queryItems
        return components.url ?? self
    }

    func appendingQueueLinkParams(
        projectKey: String,
        queueParamName: String,
        queueParamValue: String
    ) -> URL {
        guard var components = URLComponents(url: self, resolvingAgainstBaseURL: false) else {
            return self
        }

        let existing = components.queryItems ?? []
        let host = (components.host ?? "").lowercased()
        let isLiffHost = host == "liff.line.me" || host == "miniapp.line.me"

        let filtered = existing.filter { item in
            item.name != "project_key"
                && item.name != "queue_token"
                && item.name != "queue_id"
                && item.name != "liff.state"
        }

        var ordered: [URLQueryItem] = []
        if isLiffHost {
            var stateComponents = URLComponents()
            stateComponents.queryItems = [
                URLQueryItem(name: "project_key", value: projectKey),
                URLQueryItem(name: queueParamName, value: queueParamValue),
            ]
            let liffState = stateComponents.percentEncodedQuery ?? "project_key=\(projectKey)&\(queueParamName)=\(queueParamValue)"
            ordered.append(URLQueryItem(name: "liff.state", value: liffState))
        } else {
            ordered.append(URLQueryItem(name: "project_key", value: projectKey))
            ordered.append(URLQueryItem(name: queueParamName, value: queueParamValue))
        }
        ordered.append(contentsOf: filtered)

        components.queryItems = ordered
        return components.url ?? self
    }
}
