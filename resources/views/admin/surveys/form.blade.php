@extends('admin.layout')
@section('title', $view->survey ? 'アンケートを編集' : 'アンケートを作成')
@section('page-title', $view->survey ? 'アンケートを編集' : 'アンケートを作成')

@php
    use App\Domain\Survey\SurveyInviteTrigger;
    use App\Domain\Survey\SurveyQuestionType;
    use App\Http\Requests\Admin\SurveyRequest;

    $survey = $view->survey;
    $values = $view->values();
    $locked = $view->locked;
    $questions = $values?->questions ?? [];
    $dateValue = fn ($value) => $value?->format('Y-m-d\TH:i');
    // 入力エラーで戻ったときは入力中の内容、それ以外は保存済みの質問を並べる
    $rows = old('questions') ?? array_map(fn ($question) => [
        'type' => $question['type'],
        'label' => $question['label'],
        'help' => $question['help'] ?? '',
        'required' => $question['required'] ?? false,
        'allow_other' => $question['allow_other'] ?? false,
        'options' => array_map(fn ($option) => ['label' => $option, 'tag' => $question['option_tags'][$option] ?? ''], $question['options'] ?? []),
    ], $questions);
    // 白紙から作るときは NPS を1問入れておく
    if ($values === null && old('questions') === null) {
        $rows = [['type' => SurveyQuestionType::NPS, 'label' => SurveyRequest::DEFAULT_NPS_LABEL, 'required' => true]];
    }
@endphp

@if($survey)
@section('page-header-actions')
    <div class="eg-header-actions">
        <a href="{{ admin_route('admin.surveys.show', ['id' => $survey->id]) }}" class="btn btn-secondary"><i class="fas fa-chart-column" aria-hidden="true"></i> 結果を見る</a>
    </div>
@endsection
@endif

@section('content')
@include('admin.engagement._assets')
<div class="eg-page">
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.surveys.index') }}">アンケート</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $survey ? '編集' : '作成' }}</span>
</nav>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>入力内容を確認してください</strong>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ $survey ? admin_route('admin.surveys.update', ['id' => $survey->id]) : admin_route('admin.surveys.store') }}" method="POST" id="surveyForm">
    @csrf
    @if($survey) @method('PUT') @endif
    <div class="eg-studio">
        <div class="eg-studio-main">
            @if($survey === null && $view->templates !== [])
            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i></span>
                    <div><h2>雛形から始める</h2><p>選ぶと質問が入った状態になります。入力中の内容は置き換わるので、最初に選んでください。</p></div>
                </div>
                <div class="eg-templates">
                    @foreach($view->templates as $key => $template)
                    <a href="{{ admin_route('admin.surveys.create', ['template' => $key]) }}" class="eg-choice {{ request('template') === $key ? 'is-current' : '' }}" @if(request('template') === $key) aria-current="true" @endif>
                        <span class="eg-choice-icon"><i class="fas {{ $template['icon'] }}" aria-hidden="true"></i></span>
                        <span><strong>{{ $template['label'] }}</strong><small>{{ $template['count'] }}問</small></span>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon"><i class="fas fa-file-lines" aria-hidden="true"></i></span>
                    <div><h2>基本情報</h2><p>会員の画面の一番上に出ます。</p></div>
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="title">タイトル<span class="eg-required">必須</span></label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="120" value="{{ old('title', $values?->title) }}" placeholder="例: ご来店アンケート" data-survey-title>
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="description">説明</label>
                    <textarea id="description" name="description" class="form-control" rows="3" maxlength="1000" placeholder="例: より良いお店づくりのため、ご意見をお聞かせください。" data-survey-description>{{ old('description', $values?->description) }}</textarea>
                </div>
            </section>

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon"><i class="fas fa-list-check" aria-hidden="true"></i></span>
                    <div><h2>質問</h2><p>下のボタンで形式を選んで追加します。並べ替え・複製・削除は各質問の右上から。</p></div>
                </div>
                @if($locked)
                    <div class="alert alert-info" role="status">回答が集まっているため、質問は変更できません。質問を変えるときは、このアンケートを複製して編集してください。</div>
                    <ol class="eg-locked-list">
                        @foreach($questions as $question)
                        <li><i class="fas {{ SurveyQuestionType::icon($question['type']) }}" aria-hidden="true"></i> {{ $question['label'] }} <span class="eg-muted">（{{ SurveyQuestionType::getLabel($question['type']) }}）</span></li>
                        @endforeach
                    </ol>
                @else
                    <div class="eg-questions" data-survey-builder data-questions data-max="{{ SurveyRequest::MAX_QUESTIONS }}" data-nps-label="{{ SurveyRequest::DEFAULT_NPS_LABEL }}">
                        @foreach($rows as $index => $row)
                            @include('admin.surveys._question', ['index' => $index, 'row' => $row])
                        @endforeach
                    </div>
                    <div class="eg-add-types" role="group" aria-label="質問を追加">
                        @foreach(SurveyQuestionType::addable() as $type)
                        <button type="button" class="eg-add-type" data-add-type="{{ $type }}"><i class="fas {{ SurveyQuestionType::icon($type) }}" aria-hidden="true"></i> {{ SurveyQuestionType::getLabel($type) }}</button>
                        @endforeach
                    </div>
                    <p class="eg-hint eg-hint--spaced">最大{{ SurveyRequest::MAX_QUESTIONS }}問まで。NPSは1問までで、回答に応じて「NPS推奨者・中立者・批判者」のタグが付きます。</p>
                @endif
            </section>

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon eg-icon--orange"><i class="fas fa-gift" aria-hidden="true"></i></span>
                    <div><h2>回答依頼と特典</h2><p>きっかけがあった会員へ、指定した日数後の10時にお知らせとLINEで1回だけ回答依頼を送ります。</p></div>
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="invite_trigger">自動の回答依頼</label>
                    <div class="eg-row">
                        <select id="invite_trigger" name="invite_trigger" class="form-control">
                            <option value="">送らない</option>
                            @foreach(SurveyInviteTrigger::getValues() as $value => $label)
                            <option value="{{ $value }}" {{ old('invite_trigger', $values?->invite_trigger) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="eg-row-text">の</span>
                        <select name="invite_delay_days" class="form-control" aria-label="何日後に送るか">
                            @foreach([1 => '1日後', 2 => '2日後', 3 => '3日後', 7 => '7日後', 14 => '14日後', 30 => '30日後'] as $days => $label)
                            <option value="{{ $days }}" {{ (int) old('invite_delay_days', $values?->invite_delay_days ?? 1) === $days ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="eg-row-text">に送る</span>
                    </div>
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="reward_points">回答特典ポイント</label>
                    <div class="eg-input-unit">
                        <input type="number" id="reward_points" name="reward_points" class="form-control" min="0" max="100000" value="{{ old('reward_points', $values?->reward_points ?? 0) }}" data-survey-reward>
                        <span class="eg-row-text">pt</span>
                    </div>
                    <p class="eg-hint">0なら特典なし。ポイント機能を使っていないプロジェクトでは付与も案内もしません。</p>
                </div>
            </section>

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon eg-icon--gray"><i class="fas fa-calendar-days" aria-hidden="true"></i></span>
                    <div><h2>受付期間</h2><p>空欄なら期限なし。期間外は会員の画面に「受付を終了しました」と出ます。</p></div>
                </div>
                <div class="eg-row">
                    <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', $dateValue($values?->starts_at)) }}" aria-label="受付開始">
                    <span class="eg-row-text">〜</span>
                    <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" value="{{ old('ends_at', $dateValue($values?->ends_at)) }}" aria-label="受付終了">
                </div>
            </section>
        </div>

        <aside class="eg-studio-aside" aria-label="保存とプレビュー">
            <section class="eg-card eg-save">
                <input type="hidden" name="is_active" value="0">
                <div class="eg-switch-row">
                    <div><strong>回答を受け付ける</strong><small>オフにすると下書きとして保存します</small></div>
                    <label class="eg-switch"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $values?->is_active ?? true) ? 'checked' : '' }} aria-label="回答を受け付ける"><span></span></label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save" aria-hidden="true"></i> 保存</button>
                <a href="{{ admin_route('admin.surveys.index') }}" class="btn btn-secondary">一覧へ戻る</a>
            </section>
            <section class="eg-card">
                <div class="eg-card-head eg-card-head--compact">
                    <span class="eg-icon eg-icon--green"><i class="fas fa-mobile-screen" aria-hidden="true"></i></span>
                    <div><h2>プレビュー</h2><p>会員のアプリでの見え方</p></div>
                </div>
                <div class="eg-phone" aria-hidden="true"><div class="eg-phone-screen" data-survey-preview></div></div>
            </section>
        </aside>
    </div>
</form>

@if($survey)
<div class="eg-danger-zone">
    <p>アンケートを削除すると、集まった回答もすべて消えます。</p>
    <form action="{{ admin_route('admin.surveys.destroy', ['id' => $survey->id]) }}" method="POST" class="delete-form" data-item-name="アンケート「{{ $survey->title }}」と、その回答すべて">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger"><i class="fas fa-trash" aria-hidden="true"></i> 削除</button>
    </form>
</div>
@endif

@unless($locked)
<template data-survey-question-template>
    @include('admin.surveys._question', ['index' => '__Q__', 'row' => []])
</template>
<template data-survey-option-template>
    @include('admin.surveys._option', ['q' => '__Q__', 'o' => '__O__', 'option' => []])
</template>
<datalist id="survey-tag-options">
    @foreach($view->tags as $tagName)
    <option value="{{ $tagName }}"></option>
    @endforeach
</datalist>
@endunless
</div>
@endsection
