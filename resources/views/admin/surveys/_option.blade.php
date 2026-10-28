<div class="eg-option" data-q-option>
    <span class="eg-option-mark" aria-hidden="true"><i class="fa-regular fa-circle"></i></span>
    <input type="text" name="questions[{{ $q }}][options][{{ $o }}][label]" class="form-control" maxlength="100" placeholder="選択肢" aria-label="選択肢" value="{{ $option['label'] ?? '' }}">
    <div class="eg-option-tag">
        <i class="fas fa-tag" aria-hidden="true"></i>
        <input type="text" name="questions[{{ $q }}][options][{{ $o }}][tag]" class="form-control" maxlength="50" list="survey-tag-options" autocomplete="off" placeholder="タグ（任意）" aria-label="この選択肢を選んだ人に付けるタグ" value="{{ $option['tag'] ?? '' }}">
    </div>
    <button type="button" class="eg-icon-button is-danger" data-q-remove-option aria-label="この選択肢を削除"><i class="fas fa-xmark" aria-hidden="true"></i></button>
</div>
