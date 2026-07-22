//
//  Strings.swift
//  WaitingSystem
//
//  画面・メッセージの文言を集約します。多言語化時はここを Localizable に差し替え可能です。
//

import Foundation

enum Strings {

    // MARK: - 共通

    enum Common {
        static let ok = "OK"
        static let cancel = "キャンセル"
        static let close = "閉じる"
        static let save = "保存"
        static let confirm = "確定"
        static let settings = "設定"
        static let error = "エラー"
        static let loading = "処理中..."
    }

    // MARK: - ホーム

    enum Home {
        static let title = "順番待ち受付"
        static let heroTitle = "受付番号をお取りください"
        static let subtitle = "受付後にQRを読み取ると通知と待ち状況連携が有効になります"
        static let acceptButton = "受付する"
        static let currentQueueTitle = "現在の待ち状況"
        static let waitingCountLabel = "待ち組数"
        static let waitingCountValueFormat = "%d組"
        static let estimatedWaitLabel = "目安時間"
        static let estimatedWaitValueFormat = "約%d分"
        static let waitingGuideTitle = "運用フロー"
        static let waitingGuideBody = "1. 受付する\n2. QRをお客様が読み取る\n3. 店舗で呼び出すと通知"
        static let waitingListTitle = "待ち順一覧"
        static let waitingListEmpty = "待機中の方はいません"
        static let linkChannelNone = "未登録"
        static let linkChannelLine = "LINE"
        static let linkChannelApp = "アプリ"
        static let linkChannelBoth = "LINE・アプリ"
    }

    // MARK: - チケット

    enum Ticket {
        static let completedTitle = "受付完了"
        static let partySizeFormat = "人数: %d名"
        static let waitingCountFormat = "待ち人数: %d組"
        static let estimatedWaitFormat = "予想待ち時間: 約%d分"
        static let qrLineMessage = "QRを読み取るとアプリが起動します"
        static let qrScanMessage = "スマートフォンでQRを読み取ると、呼び出し通知を受け取れます"
        static let qrNotifyMessage = "順番が近づくと通知します"
        static let linkedLine = "LINEで連携済み"
        static let linkedApp = "アプリで連携済み"
        static let linkedBoth = "LINE・アプリで連携済み"
        static let autoReturnNotice = "しばらくすると受付画面に戻ります"
        static let newAccept = "新規受付"
        static let cancelTicket = "キャンセル"
        static let unlockHint = "戻るには右上の歯車から解除してください"
        static let cancelConfirmTitle = "キャンセルしますか？"
        static let cancelConfirmMessage = "この受付を取り消します。"
        static let cancelConfirmAction = "キャンセルする"
        static let goBack = "戻る"
    }

    // MARK: - パスコード

    enum Passcode {
        static let title = "管理者パスコード"
        static let placeholder = "パスコード"
        static let wrongPasscode = "パスコードが違います"
        static let unlock = "解除"
        static let clear = "クリア"
    }

    // MARK: - 人数選択

    enum PartySize {
        static let navigationTitle = "受付人数"
        static let question = "何名ですか？"
        static let selectedFormat = "%d名"
        static let optionFormat = "%d名"
        static let minusOne = "-1"
        static let plusOne = "+1"
        static let unit = "名"
    }

    // MARK: - 設定

    enum Settings {
        static let navigationTitle = "設定"
        static let serverSection = "サーバー"
        static let baseURLPlaceholder = "Base URL"
        static let liffURLPlaceholder = "LIFF URL"
        static let projectKeyPlaceholder = "プロジェクトキー"
        static let printerSection = "プリンタ"
        static let printTicketToggle = "受付時に印刷する"
        static let printDisabledHint = "オフにすると受付票を印刷しません"
        static let printerSelectedFormat = "選択中: %@"
        static let printerNotSelected = "未選択"
        static let selectPrinter = "プリンタを選択"
        static let screenSection = "画面"
        static let backgroundColor = "背景色"
        static let backgroundColorHexPlaceholder = "背景色（#RRGGBB）"
        static let securitySection = "セキュリティ"
        static let passcodeField = "解除パスコード"
        static let passcodeHint = "数字4〜8桁で入力してください"
        static let callSection = "呼び出し"
        static let callNextButton = "次を呼び出し"
        static let callingButton = "呼び出し中..."
        static let callResultFormat = "呼び出し: %@"
        static let recallButton = "再呼出"
        static let recallingButton = "送信中"
        static let recallResultFormat = "再呼び出し: %@"
        static let enterButton = "入店"
        static let enteringButton = "処理中"
        static let enterResultFormat = "入店: %@"
        static let noWaiting = "待ちがありません"
    }

    // MARK: - エラー

    enum Error {
        static let invalidURL = "URLが正しくありません。"
        static let invalidResponse = "サーバー応答が正しくありません。"
        static let serverError = "サーバーエラー"
        static let parseFailedFormat = "サーバー応答の解析に失敗しました。%@"
        static let cancelFailed = "キャンセルに失敗しました。"
        static let localhostHintFormat = "iPadからのアクセスでは localhost は使えません。PCのIPアドレスか ngrok URL を設定してください。(%@)"
    }

    // MARK: - 印刷

    enum Print {
        static let ticketTitle = "順番待ち受付票"
        static let partyFormat = "人数: %d名"
        static let receivedAtFormat = "受付: %@"
        static let waitingFormat = "待ち人数: %d組"
        static let qrLineMessage = "QRを読み込むとアプリで通知を受け取れます"
        static let qrNotifyMessage = "呼び出しが近づくと通知します"
        static let jobNameFormat = "Wait Ticket %@"
    }

    // MARK: - アクセシビリティ

    enum Accessibility {
        static let settingsButton = "設定"
    }
}
