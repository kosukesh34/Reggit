@extends('admin.layout')
@php
    use App\Domain\Survey\SurveyQuestionType;

    $survey = $view->survey;
    $responses = $view->responses;
    $npsStat = collect($view->stats)->first(fn ($stat) => $stat['question']['type'] === SurveyQuestionType::NPS);
    $percent = fn (int $count, int $total) => $total > 0 ? (int) round($count * 100 / $total) : 0;
@endphp
@section('title', $survey->title)
@section('page-title', 'アンケート結果')

@section('page-header-actions')
    <div class="eg-header-actions">
        <form action="{{ admin_route('admin.surveys.duplicate', ['id' => $survey->id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary"><i class="fas fa-copy" aria-hidden="true"></i> 複製</button>
        </form>
        <a href="{{ admin_route('admin.surveys.export', ['id' => $survey->id]) }}" class="btn btn-secondary"><i class="fas fa-file-csv" aria-hidden="true"></i> CSVで出力</a>
        <a href="{{ admin_route('admin.surveys.edit', ['id' => $survey->id]) }}" class="btn btn-primary"><i class="fas fa-pen" aria-hidden="true"></i> 編集</a>
    </div>
@endsection

@section('content')
@include('admin.engagement._assets')
<div class="eg-page">
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.surveys.index') }}">アンケート</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $survey->title }}</span>
</nav>

<div class="eg-kpis">
    <div class="eg-kpi"><small>状態</small><strong><span class="eg-badge {{ $view->isOpen ? 'is-on' : '' }}">{{ $view->isOpen ? '受付中' : '停止中' }}</span></strong><span>{{ $survey->title }}</span></div>
    <div class="eg-kpi"><small>回答数</small><strong>{{ number_format($responses->count()) }}</strong><span>件</span></div>
    <div class="eg-kpi"><small>回答率（自動の回答依頼）</small><strong>{{ $view->answerRate() !== null ? $view->answerRate().'%' : '—' }}</strong><span>依頼 {{ number_format($view->invitedCount) }}件 · 回答 {{ number_format($view->invitedAnsweredCount) }}件</span></div>
    @if($npsStat)
    <div class="eg-kpi"><small>NPS</small><strong>{{ $npsStat['nps'] === null ? '—' : ($npsStat['nps'] > 0 ? '+' : '').$npsStat['nps'] }}</strong><span>推奨者の割合 − 批判者の割合</span></div>
    @endif
</div>

<div class="eg-studio">
    <div class="eg-studio-main">
        @foreach($view->stats as $index => $stat)
        @php $type = $stat['question']['type']; @endphp
        <section class="eg-card">
            <div class="eg-q-result-head">
                <span class="eg-q-number">Q{{ $index + 1 }}</span>
                <h2>{{ $stat['question']['label'] }}</h2>
                <span class="eg-badge is-plain"><i class="fas {{ SurveyQuestionType::icon($type) }}" aria-hidden="true"></i>&nbsp;{{ SurveyQuestionType::getLabel($type) }} · {{ number_format($stat['count']) }}件</span>
            </div>
            @if($stat['count'] === 0)
                <p class="eg-muted">まだ回答がありません</p>
            @elseif($type === SurveyQuestionType::NPS)
                @php $total = $stat['promoters'] + $stat['passives'] + $stat['detractors']; @endphp
                <div class="eg-nps"><strong>{{ $stat['nps'] > 0 ? '+' : '' }}{{ $stat['nps'] }}</strong><span class="eg-muted">平均 {{ $stat['average'] }}点</span></div>
                <div class="eg-nps-stack" role="img" aria-label="推奨者{{ $percent($stat['promoters'], $total) }}%、中立者{{ $percent($stat['passives'], $total) }}%、批判者{{ $percent($stat['detractors'], $total) }}%">
                    <span class="eg-nps-detractor" style="width: {{ $percent($stat['detractors'], $total) }}%"></span>
                    <span class="eg-nps-passive" style="width: {{ $percent($stat['passives'], $total) }}%"></span>
                    <span class="eg-nps-promoter" style="width: {{ $percent($stat['promoters'], $total) }}%"></span>
                </div>
                <div class="eg-nps-rows">
                    @foreach([['推奨者（9〜10）', $stat['promoters'], 'NPS推奨者', 'eg-nps-promoter'], ['中立者（7〜8）', $stat['passives'], 'NPS中立者', 'eg-nps-passive'], ['批判者（0〜6）', $stat['detractors'], 'NPS批判者', 'eg-nps-detractor']] as [$segmentLabel, $segmentCount, $segmentTag, $segmentClass])
                    <div class="eg-nps-row">
                        <span class="eg-dot {{ $segmentClass }}" aria-hidden="true"></span>
                        <strong>{{ $segmentLabel }}</strong>
                        <span class="eg-muted">{{ $segmentCount }}人（{{ $percent($segmentCount, $total) }}%）</span>
                        @if(isset($view->npsTagIds[$segmentTag]))
                        <a href="{{ admin_route('admin.announcements.delivery.create', ['delivery_type' => 'segment', 'segment_tag_ids' => [$view->npsTagIds[$segmentTag]]]) }}" class="btn btn-sm btn-secondary"><i class="fas fa-paper-plane" aria-hidden="true"></i> この人たちへ配信</a>
                        @endif
                    </div>
                    @endforeach
                </div>
            @elseif($type === SurveyQuestionType::RATING)
                <div class="eg-nps"><strong>{{ $stat['average'] ?? '—' }}</strong><span class="eg-muted">/ 5（平均）</span></div>
                <div class="eg-bars">
                    @foreach($stat['distribution'] as $score => $count)
                    <div class="eg-bar">
                        <span class="eg-bar-label">{{ str_repeat('★', $score) }}</span>
                        <span class="eg-bar-track"><span class="eg-bar-fill" style="width: {{ $percent($count, $stat['count']) }}%"></span></span>
                        <span class="eg-bar-value">{{ $count }}件 · {{ $percent($count, $stat['count']) }}%</span>
                    </div>
                    @endforeach
                </div>
            @elseif(SurveyQuestionType::hasOptions($type))
                <div class="eg-bars">
                    @foreach($stat['options'] as $option)
                    <div class="eg-bar">
                        <span class="eg-bar-label" title="{{ $option['label'] }}">{{ $option['label'] }}</span>
                        <span class="eg-bar-track"><span class="eg-bar-fill" style="width: {{ $percent($option['count'], $stat['count']) }}%"></span></span>
                        <span class="eg-bar-value">{{ $option['count'] }}件 · {{ $percent($option['count'], $stat['count']) }}%</span>
                    </div>
                    @endforeach
                </div>
                @if($type === SurveyQuestionType::MULTI_CHOICE)
                <p class="eg-hint eg-hint--spaced">複数選択のため、割合は回答者のうち選んだ人の割合です（合計は100%を超えます）。</p>
                @endif
                @if(($stat['others'] ?? []) !== [])
                <h3 class="eg-label eg-hint--spaced">「その他」の内容</h3>
                <ul class="eg-quotes">@foreach($stat['others'] as $other)<li>{{ $other }}</li>@endforeach</ul>
                @endif
            @elseif($type === SurveyQuestionType::NUMBER)
                <div class="eg-kpis">
                    <div class="eg-kpi"><small>平均</small><strong>{{ $stat['average'] ?? '—' }}</strong></div>
                    <div class="eg-kpi"><small>最小</small><strong>{{ $stat['min'] ?? '—' }}</strong></div>
                    <div class="eg-kpi"><small>最大</small><strong>{{ $stat['max'] ?? '—' }}</strong></div>
                </div>
            @else
                <ul class="eg-quotes">@foreach($stat['texts'] as $text)<li>{{ $text }}</li>@endforeach</ul>
            @endif
        </section>
        @endforeach
    </div>

    <aside class="eg-studio-aside">
        <section class="eg-card eg-share-qr">
            <div class="eg-card-head eg-card-head--compact">
                <span class="eg-icon"><i class="fas fa-qrcode" aria-hidden="true"></i></span>
                <div><h2>回答ページ</h2><p>お知らせ・LINE・店頭POPに載せると、会員がこのアンケートに回答できます。</p></div>
            </div>
            <div data-issued-qr data-url="{{ $view->shareUrl }}" data-filename="survey-{{ $survey->id }}.png" aria-label="アンケートのQRコード"></div>
            <p class="eg-url">{{ $view->shareUrl }}</p>
            <button type="button" class="btn btn-primary btn-block" data-save-issued-qr disabled><i class="fas fa-download" aria-hidden="true"></i> QR画像を保存</button>
            <p class="text-danger" data-qr-error hidden role="alert"></p>
        </section>
        <section class="eg-card">
            <div class="eg-card-head eg-card-head--compact">
                <span class="eg-icon eg-icon--gray"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></span>
                <div><h2>最近の回答</h2></div>
            </div>
            @if($responses->isEmpty())
                <p class="eg-muted">まだ回答がありません</p>
            @else
            <ul class="eg-recent">
                @foreach($responses->take(10) as $response)
                <li>
                    @if($response->member)
                    <a href="{{ admin_route('admin.members.detail', ['id' => $response->member_id]) }}">{{ $response->member->member_number }}</a>
                    @else
                    <span class="eg-muted">退会済み</span>
                    @endif
                    <span class="eg-muted">{{ $response->answered_at?->format('m/d H:i') }}{{ $response->nps_score !== null ? ' · NPS '.$response->nps_score : '' }}</span>
                </li>
                @endforeach
            </ul>
            @endif
        </section>
    </aside>
</div>
</div>
@endsection
