import UIKit

final class PrinterService: PrinterServiceProtocol {
    private let settingsStore: SettingsStoreProtocol
    private let liffResolver: LiffURLResolving

    init(settingsStore: SettingsStoreProtocol = SettingsStore(), liffResolver: LiffURLResolving = LiffURLResolver()) {
        self.settingsStore = settingsStore
        self.liffResolver = liffResolver
    }

    func selectPrinter(from presenter: UIViewController, completion: @escaping (UIPrinter?) -> Void) {
        let settings = settingsStore.load()
        let selectedPrinter: UIPrinter? = settings.printerURL.flatMap { URL(string: $0) }.map { UIPrinter(url: $0) }
        let picker = UIPrinterPickerController(initiallySelectedPrinter: selectedPrinter)
        picker.present(from: presenter.view.bounds, in: presenter.view, animated: true) { [settingsStore] controller, userDidSelect, error in
            guard error == nil else { completion(nil); return }
            if userDidSelect, let printer = controller.selectedPrinter {
                var updated = settings
                updated.printerURL = printer.url.absoluteString
                updated.printerName = printer.displayName
                settingsStore.save(updated)
                completion(printer)
            } else {
                completion(nil)
            }
        }
    }

    func printTicket(_ ticket: QueueTicket, baseURL: String, projectKey: String, from presenter: UIViewController) {
        let settings = settingsStore.load()
        let renderer = TicketPrintRenderer(liffResolver: liffResolver)
        let image = renderer.render(ticket: ticket, settings: settings)

        let printController = UIPrintInteractionController.shared
        let info = UIPrintInfo(dictionary: nil)
        info.outputType = .photo
        info.jobName = String(format: Strings.Print.jobNameFormat, ticket.queueNumber)
        printController.printInfo = info
        printController.printingItem = image

        if let printerURL = settings.printerURL, let url = URL(string: printerURL) {
            let printer = UIPrinter(url: url)
            printer.contactPrinter { _ in
                printController.print(to: printer, completionHandler: nil)
            }
        } else {
            printController.present(animated: true, completionHandler: nil)
        }
    }
}

// MARK: - Ticket Print Renderer (SRP: rendering only)

final class TicketPrintRenderer {
    private let liffResolver: LiffURLResolving

    init(liffResolver: LiffURLResolving = LiffURLResolver()) {
        self.liffResolver = liffResolver
    }

    func render(ticket: QueueTicket, settings: AppSettings) -> UIImage {
        let size = CGSize(width: AppConstants.Print.ticketImageWidth, height: AppConstants.Print.ticketImageHeight)
        let renderer = UIGraphicsImageRenderer(size: size)
        return renderer.image { ctx in
            UIColor.white.setFill()
            ctx.fill(CGRect(origin: .zero, size: size))

            let paragraph = NSMutableParagraphStyle()
            paragraph.alignment = .center

            draw(Strings.Print.ticketTitle, font: .boldSystemFont(ofSize: 36), at: CGPoint(x: size.width / 2, y: 40), paragraph: paragraph)
            draw("No.\(ticket.queueNumber)", font: .boldSystemFont(ofSize: 64), at: CGPoint(x: size.width / 2, y: 110), paragraph: paragraph)
            draw(String(format: Strings.Print.partyFormat, ticket.partySize), font: .systemFont(ofSize: 28), at: CGPoint(x: size.width / 2, y: 200), paragraph: paragraph)

            let time = DateFormatter.localizedString(from: ticket.createdAt, dateStyle: .medium, timeStyle: .short)
            draw(String(format: Strings.Print.receivedAtFormat, time), font: .systemFont(ofSize: 24), at: CGPoint(x: size.width / 2, y: 250), paragraph: paragraph)
            draw(String(format: Strings.Print.waitingFormat, ticket.waitingCount), font: .systemFont(ofSize: 24), at: CGPoint(x: size.width / 2, y: 290), paragraph: paragraph)

            let qrText = liffResolver.resolve(settings: settings, qrToken: ticket.qrToken, ticketId: ticket.id)
            if let qr = QRCodeGenerator().generate(from: qrText, size: AppConstants.Print.ticketQRSize) {
                let qrS = AppConstants.Print.ticketQRSize
                qr.draw(in: CGRect(x: (size.width - qrS) / 2, y: 340, width: qrS, height: qrS))
            }

            draw(Strings.Print.qrLineMessage, font: .systemFont(ofSize: 18), at: CGPoint(x: size.width / 2, y: 640), paragraph: paragraph)
            draw(Strings.Print.qrNotifyMessage, font: .systemFont(ofSize: 18), at: CGPoint(x: size.width / 2, y: 670), paragraph: paragraph)
        }
    }

    private func draw(_ text: String, font: UIFont, at point: CGPoint, paragraph: NSParagraphStyle) {
        let attributes: [NSAttributedString.Key: Any] = [
            .font: font,
            .paragraphStyle: paragraph,
            .foregroundColor: UIColor.black
        ]
        let size = text.size(withAttributes: attributes)
        text.draw(in: CGRect(x: point.x - size.width / 2, y: point.y, width: size.width, height: size.height), withAttributes: attributes)
    }
}
