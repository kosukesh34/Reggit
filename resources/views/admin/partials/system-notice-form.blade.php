<div class="form-group"><label>タイトル<input name="title" class="form-control" value="{{ old('title', $notice?->title) }}" maxlength="255" required></label></div>
<div class="form-group notice-editor">
    <label>本文（Markdown）<textarea name="body" class="form-control" rows="8" required>{{ old('body', $notice?->body) }}</textarea></label>
    <div class="mt-3">
        <label>文字色 <input type="color" value="#b91c1c" aria-label="お知らせの文字色"></label>
        <button type="button" class="btn btn-secondary" onclick="const editor = this.closest('.notice-editor'); const area = editor.querySelector('textarea'); const color = editor.querySelector('input[type=color]').value; const start = area.selectionStart; const end = area.selectionEnd; const text = area.value.slice(start, end) || '色を付ける文字'; area.setRangeText('[color=' + color + ']' + text + '[/color]', start, end, 'select'); area.focus(); area.dispatchEvent(new Event('input', {bubbles: true}));">選択した文字に色を付ける</button>
    </div>
    <p class="form-text text-muted">本文内の文字を選択してから色を付けてください。</p>
    <button type="button" class="btn btn-secondary mt-3" data-notice-preview>編集中の内容をプレビュー</button>
    <section class="card mt-3" data-notice-preview-panel hidden aria-live="polite">
        <div class="card-header"><h3 class="card-title">プレビュー</h3></div>
        <div class="card-body"><h4 data-preview-title></h4><div class="notice-markdown" data-preview-body></div><p data-preview-error role="alert" hidden></p></div>
    </section>
    <details>
        <summary>Markdownの書き方</summary>
        <pre>## 見出し
**太字** / *斜体*
- 箇条書き
[ヘルプを見る](https://example.com)
[color=#b91c1c]重要なお知らせ[/color]</pre>
    </details>
    @if($notice)
        <details class="mt-3"><summary>保存済みの本文プレビュー</summary><div class="notice-markdown">{!! app(\App\Support\NoticeMarkdown::class)->toHtml($notice->body) !!}</div></details>
    @endif
</div>
<div class="form-group"><label>公開開始（空欄で即時）<input type="datetime-local" name="starts_at" class="form-control" value="{{ $notice?->starts_at?->format('Y-m-d\TH:i') }}"></label></div>
<div class="form-group"><label>公開終了（空欄で無期限）<input type="datetime-local" name="ends_at" class="form-control" value="{{ $notice?->ends_at?->format('Y-m-d\TH:i') }}"></label></div>
<input type="hidden" name="is_published" value="0">
<label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $notice?->is_published ?? true))> 公開する</label>

<p class="form-text text-muted">「公開する」が未選択の場合は下書きとして保存され、店舗のダッシュボードには表示されません。</p>
