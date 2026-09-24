<?php

namespace App\Support;

final class HelpManual
{
    /**
     * @return list<array{slug: string, title: string, summary: string, steps: list<array{title: string, body: string, image: string, alt: string, caption: string, mark: string}>, note: string}>
     */
    public function articles(): array
    {
        return [
            [
                'slug' => 'transfer',
                'title' => '会員を別の端末へ引き継ぐ',
                'summary' => '会員詳細からコードを発行し、新しい端末の会員証で受け取ります。',
                'note' => 'コードは一度だけ表示され、30分で無効になります。別の会員が入っている端末では、退会してから入れてください。',
                'steps' => [
                    [
                        'title' => '会員一覧でIDを確認する',
                        'body' => '左メニューの「会員」を開きます。赤枠のIDを控えます。',
                        'image' => 'images/help/help-members.png',
                        'alt' => '会員一覧のID列',
                        'caption' => '赤枠が会員IDです。',
                        'mark' => 'help-mark--member-id',
                    ],
                    [
                        'title' => '会員詳細でコードを発行する',
                        'body' => 'その会員の詳細を開き、「引き継ぎ」の「コードを発行」を押します。',
                        'image' => 'images/help/help-member-transfer.png',
                        'alt' => '引き継ぎコードの入力画面',
                        'caption' => '赤枠がコードを発行する場所です。発行後のコードは詳細画面に一度だけ出ます。',
                        'mark' => 'help-mark--transfer-form',
                    ],
                    [
                        'title' => '新しい端末のメニューで受け取る',
                        'body' => '右上のメニューを開き、「引き継ぎ」に会員IDとコードを入れます。',
                        'image' => 'images/help/help-app-more.png',
                        'alt' => 'アプリのメニュー',
                        'caption' => '赤枠がメニューを開く場所です。開いた中の「引き継ぎ」に会員IDとコードを入れます。',
                        'mark' => 'help-mark--app-more',
                    ],
                ],
            ],
            [
                'slug' => 'coupon',
                'title' => 'クーポンを配信する',
                'summary' => '対象と日時を選んでクーポンを配ります。',
                'note' => '通知をオフにすると発行のみです。期限切れのクーポンは選べません。',
                'steps' => [
                    [
                        'title' => 'クーポン一覧から配信を開く',
                        'body' => '左メニューの「クーポン」を開き、右上の「クーポンを配信」をクリックします。',
                        'image' => 'images/help/help-coupons.png',
                        'alt' => 'クーポン一覧の配信ボタン',
                        'caption' => '赤枠が「クーポンを配信」です。',
                        'mark' => 'help-mark--coupon-button',
                    ],
                    [
                        'title' => 'タイミングと対象を選ぶ',
                        'body' => '即時か予約、全会員か条件を選び、右側の人数を確認してから配信します。',
                        'image' => 'images/help/help-coupon-delivery.png',
                        'alt' => 'クーポン配信の対象選択',
                        'caption' => '赤枠が配信タイミングと対象です。',
                        'mark' => 'help-mark--coupon-target',
                    ],
                ],
            ],
            [
                'slug' => 'analytics',
                'title' => '分析を確認して全員に配信する',
                'summary' => '期間の数字を見てから、有効なクーポンを全員へ今すぐ配ります。',
                'note' => '条件を分ける配信は「クーポンを配信」を使います。この配信は全員への即時配布だけです。',
                'steps' => [
                    [
                        'title' => '分析で期間を集計する',
                        'body' => '左メニューの「分析」で開始日と終了日を入れ、「集計」を押します。',
                        'image' => 'images/help/help-analytics.png',
                        'alt' => '分析の期間とサマリー',
                        'caption' => '赤枠が集計する期間です。',
                        'mark' => 'help-mark--analytics-period',
                    ],
                    [
                        'title' => '分析から配信を実行する',
                        'body' => '「分析から配信」でクーポンを選び、「全員に今すぐ配信」を押します。',
                        'image' => 'images/help/help-analysis-delivery.png',
                        'alt' => '分析から配信の実行ボタン',
                        'caption' => '赤枠が店舗から実行する配信です。',
                        'mark' => 'help-mark--analysis-run',
                    ],
                ],
            ],
            [
                'slug' => 'home',
                'title' => 'お客様向けの画面を変える',
                'summary' => 'SkipとLINEのタブと画像は、ホーム・メニューの同じ設定です。',
                'note' => '保存するとSkipとLINEの両方に反映されます。',
                'steps' => [
                    [
                        'title' => 'ホーム・メニューを開く',
                        'body' => '左メニューの「設定」から「ホーム・メニュー」を開きます。',
                        'image' => 'images/help/help-settings.png',
                        'alt' => '設定一覧のホーム・メニュー',
                        'caption' => '赤枠が共通画面の設定です。',
                        'mark' => 'help-mark--home-menu',
                    ],
                    [
                        'title' => '画像と並びを保存する',
                        'body' => 'ヘッダー画像、フッター、ホームの項目を変えて「保存する」を押します。',
                        'image' => 'images/help/help-home-settings.png',
                        'alt' => 'ホーム設定のヘッダー',
                        'caption' => '赤枠がヘッダー画像と保存です。',
                        'mark' => 'help-mark--home-header',
                    ],
                ],
            ],
            [
                'slug' => 'withdraw',
                'title' => 'Skipで退会する',
                'summary' => '退会はハンバーガーメニューから、会員本人が行います。',
                'note' => '退会するとポイントとスタンプは戻りません。',
                'steps' => [
                    [
                        'title' => 'メニューの退会を開く',
                        'body' => '右上のメニューを開き、「退会」を押します。会員でない場合はこの項目は出ません。',
                        'image' => 'images/help/help-app-more.png',
                        'alt' => 'アプリのメニュー',
                        'caption' => '赤枠がメニューを開く場所です。開いた中の「退会」から手続きします。',
                        'mark' => 'help-mark--app-member',
                    ],
                ],
            ],
            $this->faq('delivery-cancel', '確認画面のキャンセルでは配信されない', 'キャンセルは画面を閉じるだけで、クーポンは発行されません。', <<<'MD'
クーポン配信の確認前にある「キャンセル」は、クーポン一覧へ戻るだけです。配信は作成されず、会員にクーポンは発行されません。

分析からの「全員に今すぐ配信」には確認画面がありません。押した時点で発行を試みます。
MD),
            $this->faq('delivery-immediate-error', '即時配信が失敗したとき', 'エラーが出た場合、クーポンは発行されていません。', <<<'MD'
期限切れのクーポンや、対象会員がいないなどの入力エラーは、画面に理由が表示されます。このときクーポンは発行されません。

それ以外の失敗は「配信の実行に失敗しました」と表示されます。こちらも発行は完了していません。配布履歴には残りません。

分析AI向けAPIも同じです。入力エラーは 422、それ以外の失敗は 500 を返し、クーポンは発行しません。
MD),
            $this->faq('delivery-scheduled-error', '予約配信が失敗したとき', '失敗した予約はクーポンを発行せず、状態が「失敗」になります。', <<<'MD'
予約した配信は、指定時刻に対象を計算し直してから発行します。個人を指定した配信だけ、予約時の会員のまま発行します。

対象の計算に失敗した場合、状態は「失敗」になります。失敗理由は配信に残り、クーポンは発行されません。

全員配信以外で、実行時点の対象が 0 人のときは失敗ではなく「配布済み」で件数 0 です。

すでに予約中でない配信に対して同じ処理が動いても、何も変更しません。二重に発行しません。
MD),
            $this->faq('delivery-sent-no-undo', '配布済みはまとめて取り消せない', '配信のキャンセルで、発行済みクーポンは回収しません。', <<<'MD'
配布済みのクーポンを、配信のキャンセル操作でまとめて回収する機能はありません。個別の会員クーポンは会員詳細から削除できます。
MD),
            $this->faq('existing-link-rejected', '既存会員連携できないとき', '条件が一致しない、またはすでに結びついている会員は連携しません。', <<<'MD'
店舗が選んだ項目がすべて一致し、まだこのアプリに結びついていない会員だけ連携できます。

一致する会員がいない場合、または複数いる場合は連携しません。

メールアドレスを条件にしているときは、確認メールのリンクを開くまで会員は作成されません。リンクの有効期限は30分です。
MD),
        ];
    }

    /**
     * @return list<array{slug: string, title: string, summary: string, steps: list<array{title: string, body: string, image: string, alt: string, caption: string, mark: string}>, note: string}>
     */
    /**
     * @return array{slug: string, title: string, summary: string, category: string, note: string, steps: array<int, array<string, string>>, body: string}
     */
    private function faq(string $slug, string $title, string $summary, string $body): array
    {
        return [
            'slug' => $slug,
            'title' => $title,
            'summary' => $summary,
            'category' => 'faq',
            'note' => '',
            'steps' => [],
            'body' => $body,
        ];
    }

    public function matching(string $query): array
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return $this->articles();
        }

        return array_values(array_filter($this->articles(), function (array $article) use ($needle): bool {
            $haystack = mb_strtolower($article['title'].' '.$article['summary'].' '.($article['body'] ?? ''));

            return str_contains($haystack, $needle);
        }));
    }

    /**
     * @return array{slug: string, title: string, summary: string, steps: list<array{title: string, body: string, image: string, alt: string, caption: string, mark: string}>, note: string}|null
     */
    public function find(string $slug): ?array
    {
        foreach ($this->articles() as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }

        return null;
    }
}
