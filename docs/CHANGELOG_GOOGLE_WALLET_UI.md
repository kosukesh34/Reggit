# Google Wallet 会員証 UI 改善の変更まとめ

## 概要
Google Wallet の会員証を「QRコード → バーコード（CODE_128）」に変更し、表示ラベルを日本語化。さらに Android / Web / iOS（Google Wallet アプリ）で同じデザインになるようテンプレートを統一した。

---

## 主な変更内容

### 1. バーコード表示への変更
- **QR_CODE** → **CODE_128**（1次元バーコード）に変更
- ポイントカード（会員証）のアプリ側UIと同じバーコード形式に統一
- 既存パスがキャッシュされないよう、オブジェクトIDにサフィックス `-b` を付与（`member-{id}-b`, `demo-{id}-b`）し、新規オブジェクトとして発行

### 2. 日本語ラベル・表示の統一
- **LoyaltyClass**: `accountNameLabel`「会員名」、`accountIdLabel`「会員番号」、`rewardsTierLabel`「ランク」
- **LoyaltyObject**: `loyaltyPoints.label`「ポイント」、`textModulesData` のヘッダーを「ランク」「ポイント」「会員番号」に変更
- JWT に**常にクラス定義を含める**ようにし、サーバー側の古いクラス定義に依存しないよう修正

### 3. クロスプラットフォーム同一デザイン
- **classTemplateInfo** を追加
  - `cardTemplateOverride`: カードの行構成を固定（1行目: 会員名 | ポイント、2行目: 会員番号）
  - `listTemplateOverride`: 一覧表示の1行目・2行目を統一
  - `detailsTemplateOverride`: 詳細の表示順を統一（ランク → ポイント → 会員番号）
- **wideProgramLogo** を指定し、Android でも Web/iOS と同様のヘッダー表示に

### 4. その他
- **検証用**: 会員未登録でも URL 発行できる `GOOGLE_WALLET_ALLOW_DEMO_USER` を追加
- **背景色**: `GOOGLE_WALLET_HEX_BACKGROUND_COLOR` でカード背景色を指定可能に
- **クラス識別子**: デフォルトを `membership_v3` に変更（新デザインのカードとして発行）
- 既存クラスに日本語ラベルを反映するため、REST API で PATCH（24h キャッシュ付き）
- 検証用ページ `public/test-google-wallet-add.html` を追加（キャッシュ対策・`?debug=1` で JWT 内容確認可能）
- `docs/GOOGLE_WALLET_SETUP.md` に「デザイン変更が反映されない場合」の説明を追記

---

## 変更ファイル一覧（Google Wallet 関連）

| ファイル | 変更内容 |
|----------|----------|
| `config/google_wallet.php` | allow_demo_user, hex_background_color, class_suffix デフォルト membership_v3 |
| `app/Services/GoogleWalletPassService.php` | バーコード CODE_128、日本語ラベル、JWT に常にクラス含む、classTemplateInfo、wideProgramLogo、object -b サフィックス、PATCH |
| `app/Http/Controllers/Member/GoogleWalletUrlController.php` | デモ時 allow_demo_user、objectSuffix に -b |
| `docs/GOOGLE_WALLET_SETUP.md` | デザイン反映の注意書き・背景色の説明 |
| `.env.example` / `env.example` | GOOGLE_WALLET_ALLOW_DEMO_USER, HEX_BACKGROUND_COLOR, CLASS_SUFFIX |
| `public/test-google-wallet-add.html` | 新規（検証用・自動リダイレクト・debug=1） |

※ 上記以外に、ランク設定・会員証プレビュー・管理画面など、同ブランチで行った変更も含まれる場合があります。

---

## 動作確認のポイント

1. 既存の Google Wallet 会員証は**削除**してから「Google Walletに追加」し直すと、新デザイン（バーコード・日本語）が反映される。
2. 検証用: `https://{host}/test-google-wallet-add.html` で自動リダイレクト。`?debug=1` で JWT の barcode.type やラベルを確認可能。
3. 本番では `GOOGLE_WALLET_ALLOW_DEMO_USER` を `false` にすること。
