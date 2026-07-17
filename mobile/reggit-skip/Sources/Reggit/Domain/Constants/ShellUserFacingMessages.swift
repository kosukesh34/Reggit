import Foundation

enum ShellUserFacingMessages {
    static let connectionTitle = "接続できません"
    static let loadFailedTitle = "読み込みに失敗しました"

    static let unreachableMessage = """
    インターネット接続を確認して、もう一度お試しください。
    Wi-Fi やモバイルデータがオフになっていないかもご確認ください。
    """

    static let whiteScreenMessage = """
    画面の表示に時間がかかっています。
    通信環境のよい場所でもう一度読み込み直してください。
    """
}
