            <div class="card card-mb-24">
                <div class="card-header"><h3 class="card-title">会員証ページのスタイル</h3></div>
                <div class="card-body">
                    <label for="member_page_style" class="form-label">ページ全体のデザイン</label>
                    <select id="member_page_style" name="member_page_style" class="form-control">
                        @foreach(['classic' => 'スタンダード', 'wallet' => 'ポイント', 'minimal' => 'シンプル', 'ticket' => 'パス'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('member_page_style', $view->memberPageStyle ?? 'classic') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('member_page_style')<p class="text-danger">{{ $message }}</p>@enderror
                    <p class="form-text text-muted">会員証ページの背景・カード・情報エリアをまとめて切り替えます。</p>
                </div>
            </div>
            <div class="card card-mb-24">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye"></i> 会員証の表示</h3>
                </div>
                <div class="card-body">

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">表示オプション</h4>
                        <div class="ranks-settings-checkboxes">
                            <label class="form-check" id="show_points_display_wrap">
                                <input type="hidden" name="show_points_display" value="0">
                                <input type="checkbox" name="show_points_display" value="1" {{ old('show_points_display', $view->showPointsDisplay ?? true) ? 'checked' : '' }}>
                                ポイント数を表示する
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="show_rank_progress" value="0">
                                <input type="checkbox" name="show_rank_progress" value="1" {{ old('show_rank_progress', $view->showRankProgress ?? true) ? 'checked' : '' }}>
                                ランクアップまでの条件を表示する
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="show_member_card_for_guest" value="0">
                                <input type="checkbox" name="show_member_card_for_guest" value="1" {{ old('show_member_card_for_guest', $view->showMemberCardForGuest ?? true) ? 'checked' : '' }}>
                                ゲストにも会員証エリアを表示する
                            </label>
                        </div>
                        <p class="form-text text-muted">オフにすると会員未登録時は「会員登録すると会員証が表示されます」などのメッセージのみ表示します。</p>
                    </div>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">会員証画面の情報</h4>
                        <p class="form-text text-muted">氏名・電話番号などのユーザー情報は表示せず、会員証の下に表示する情報を選択できます。</p>
                        <div class="ranks-settings-checkboxes">
                            @foreach([
                                'member_show_rank_progress' => ['次のランクまでの進捗', $view->memberShowRankProgress ?? true],
                                'member_show_point_history' => ['ポイント付与・利用履歴', $view->memberShowPointHistory ?? true],
                                'member_show_coupon_activity' => ['最近獲得したクーポン', $view->memberShowCouponActivity ?? true],
                            ] as $field => [$label, $enabled])
                                <label class="form-check">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $enabled) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">デザイン・表示形式</h4>
                        <div class="form-group">
                            <label for="member_card_design" class="form-label">会員証デザイン</label>
                            <select name="member_card_design" id="member_card_design" class="form-control">
                                @foreach(['default' => 'スタンダード', 'compact' => 'シンプル', 'ticket' => 'チケット', 'minimal_line' => 'ミニマル', 'split_banner' => 'スプリット', 'logo_header' => 'レジ提示', 'logo_top' => 'ポイント', 'wallet' => 'ウォレット', 'emblem' => 'デジタルパス', 'logo_center' => 'ロゴ中央'] as $designKey => $designLabel)
                                    <option value="{{ $designKey }}" {{ old('member_card_design', $view->memberCardDesign ?? 'default') === $designKey ? 'selected' : '' }}>{{ $designLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ranks-settings-fields ranks-settings-fields--wide">
                            <div class="form-group">
                                <label for="member_card_display_type" class="form-label">表示形式</label>
                                <select name="member_card_display_type" id="member_card_display_type" class="form-control">
                                    <option value="card" {{ old('member_card_display_type', $view->memberCardDisplayType ?? 'card') === 'card' ? 'selected' : '' }}>カード型（裏表・タップで裏返し）</option>
                                    <option value="sheet" {{ old('member_card_display_type', $view->memberCardDisplayType ?? '') === 'sheet' ? 'selected' : '' }}>シート型（1面）</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_card_code_type" class="form-label">会員番号の表示</label>
                                <select name="member_card_code_type" id="member_card_code_type" class="form-control">
                                    <option value="barcode" {{ old('member_card_code_type', $view->memberCardCodeType ?? 'barcode') === 'barcode' ? 'selected' : '' }}>バーコード</option>
                                    <option value="qrcode" {{ old('member_card_code_type', $view->memberCardCodeType ?? '') === 'qrcode' ? 'selected' : '' }}>QRコード</option>
                                </select>
                            </div>
                        </div>
                        <p class="form-text text-muted">ロゴ画像は <a href="{{ admin_route('admin.settings.app-branding') }}">店舗名・画像</a> のロゴ画像です。</p>
                    </div>
                </div>
            </div>
