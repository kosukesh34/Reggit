import Foundation
import UIKit

// MARK: - API Client Protocol (DIP)

protocol QueueAPIClientProtocol: Sendable {
    func createTicket(partySize: Int) async throws -> QueueTicket
    func cancelTicket(id: Int) async throws
    func callNext() async throws -> QueueTicket?
    func callTicket(id: Int) async throws -> QueueTicket
    func recallTicket(id: Int) async throws -> QueueTicket
    func enterTicket(id: Int) async throws -> QueueTicket
    func fetchTicketStatus(id: Int) async throws -> QueueTicket?
    func fetchQueueOverview() async throws -> QueueOverview
    func fetchQueueList() async throws -> [QueueListEntry]
}

// MARK: - Settings Store Protocol (DIP)

protocol SettingsStoreProtocol {
    func load() -> AppSettings
    func save(_ settings: AppSettings)
}

// MARK: - Printer Service Protocol (DIP)

protocol PrinterServiceProtocol {
    func selectPrinter(from presenter: UIViewController, completion: @escaping (UIPrinter?) -> Void)
    func printTicket(_ ticket: QueueTicket, baseURL: String, projectKey: String, from presenter: UIViewController)
}

// MARK: - LIFF URL Resolver Protocol (SRP)

protocol LiffURLResolving {
    func resolve(settings: AppSettings, qrToken: String?, ticketId: Int) -> String
}
