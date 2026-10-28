@php
    use App\Domain\Survey\SurveyQuestionType;

    $type = (string) ($row['type'] ?? SurveyQuestionType::CHOICE);
    $options = array_values($row['options'] ?? []);
    // 選択式は最初から2つの入力欄を出す
    while (count($options) < 2) {
        $options[] = ['label' => '', 'tag' => ''];
    }
@endphp
<section class="eg-q" data-q data-index="{{ $index }}" data-type="{{ $type }}">
    <div class="eg-q-head">
        <span class="eg-q-number" data-q-number>Q</span>
        <select name="questions[{{ $index }}][type]" class="form-control eg-q-type" aria-label="質問の形式" data-q-type>
            @foreach(SurveyQuestionType::addable() as $value)
            <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ SurveyQuestionType::getLabel($value) }}</option>
            @endforeach
        </select>
        <div class="eg-q-tools">
            <button type="button" class="eg-icon-button" data-q-move="up" aria-label="上へ移動"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
            <button type="button" class="eg-icon-button" data-q-move="down" aria-label="下へ移動"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
            <button type="button" class="eg-icon-button" data-q-duplicate aria-label="この質問を複製"><i class="fas fa-copy" aria-hidden="true"></i></button>
            <button type="button" class="eg-icon-button is-danger" data-q-remove aria-label="この質問を削除"><i class="fas fa-trash" aria-hidden="true"></i></button>
        </div>
    </div>
    <div class="eg-q-body">
        <input type="text" name="questions[{{ $index }}][label]" class="form-control eg-q-label" maxlength="200" placeholder="質問文を入力" aria-label="質問文" value="{{ $row['label'] ?? '' }}" data-q-label>
        <input type="text" name="questions[{{ $index }}][help]" class="form-control" maxlength="300" placeholder="補足説明（任意）例: 当てはまるものをすべて選んでください" aria-label="補足説明" value="{{ $row['help'] ?? '' }}" data-q-help>
        <div data-q-options-panel>
            <div class="eg-options" data-q-options>
                @foreach($options as $o => $option)
                    @include('admin.surveys._option', ['q' => $index, 'o' => $o, 'option' => $option])
                @endforeach
            </div>
            <button type="button" class="eg-link-button" data-q-add-option><i class="fas fa-plus" aria-hidden="true"></i> 選択肢を追加</button>
            <p class="eg-hint">タグを入れると、その選択肢を選んだ会員にタグが付き、セグメント配信に使えます。</p>
        </div>
    </div>
    <div class="eg-q-footer">
        <input type="hidden" name="questions[{{ $index }}][required]" value="0">
        <label>
            <span class="eg-switch eg-switch--sm"><input type="checkbox" name="questions[{{ $index }}][required]" value="1" {{ ($row['required'] ?? false) ? 'checked' : '' }} data-q-required><span></span></span>
            回答必須
        </label>
        <input type="hidden" name="questions[{{ $index }}][allow_other]" value="0" data-q-other>
        <label data-q-other>
            <span class="eg-switch eg-switch--sm"><input type="checkbox" name="questions[{{ $index }}][allow_other]" value="1" {{ ($row['allow_other'] ?? false) ? 'checked' : '' }} data-q-allow-other><span></span></span>
            「その他（自由入力）」を出す
        </label>
    </div>
</section>
