@extends('admin.layout')
@section('title', 'アンケート')
@section('page-title', 'アンケート')

@section('page-header-actions')
    <div class="eg-header-actions">
        <a href="{{ admin_route('admin.surveys.create') }}" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> アンケートを作成</a>
    </div>
@endsection

@section('content')
@include('admin.engagement._assets')
<div class="eg-page">
<p class="eg-lead">来店・注文・予約・会員登録のあとに自動で回答依頼を送ったり、回答者へポイントを付けたりできます。NPSの回答や、タグを設定した選択肢を選んだ会員にはタグが付くので、そのままセグメント配信に使えます。</p>

<section class="eg-card">
    @if($surveys->isEmpty())
        <div class="eg-empty">
            <i class="fas fa-square-poll-vertical" aria-hidden="true"></i>
            <h3>まだアンケートがありません</h3>
            <p>雛形を選ぶと、質問が入った状態から始められます。</p>
            <a href="{{ admin_route('admin.surveys.create', ['template' => 'visit']) }}" class="btn btn-primary">来店満足度の雛形で作る</a>
            <a href="{{ admin_route('admin.surveys.create') }}" class="btn btn-secondary">白紙から作る</a>
        </div>
    @else
        <div class="eg-table-wrap">
        <table class="eg-table">
            <thead>
                <tr><th>アンケート</th><th>状態</th><th>回答</th><th>特典</th><th>自動の回答依頼</th><th class="table-actions-cell"><span class="sr-only">操作</span></th></tr>
            </thead>
            <tbody>
                @foreach($surveys as $survey)
                @php $definition = $survey->toDefinition(); @endphp
                <tr>
                    <td>
                        <a href="{{ admin_route('admin.surveys.show', ['id' => $survey->id]) }}" class="eg-table-title">{{ $survey->title }}</a>
                        <span class="eg-table-sub">{{ count($definition->questions) }}問 · {{ $survey->created_at?->format('Y/m/d') }} 作成</span>
                    </td>
                    <td><span class="eg-badge {{ $definition->isOpen(now()) ? 'is-on' : '' }}">{{ $definition->isOpen(now()) ? '受付中' : '停止中' }}</span></td>
                    <td><strong>{{ number_format($survey->answered_count) }}</strong> 件</td>
                    <td>{{ $survey->reward_points > 0 ? number_format($survey->reward_points).'pt' : '—' }}</td>
                    <td>{{ $survey->invite_trigger !== null ? \App\Domain\Survey\SurveyInviteTrigger::getLabel($survey->invite_trigger).'の'.$survey->invite_delay_days.'日後' : '送らない' }}</td>
                    <td>
                        <div class="eg-actions">
                            <a href="{{ admin_route('admin.surveys.show', ['id' => $survey->id]) }}" class="eg-icon-button" aria-label="{{ $survey->title }}の結果"><i class="fas fa-chart-column" aria-hidden="true"></i></a>
                            <a href="{{ admin_route('admin.surveys.edit', ['id' => $survey->id]) }}" class="eg-icon-button" aria-label="{{ $survey->title }}を編集"><i class="fas fa-pen" aria-hidden="true"></i></a>
                            <form action="{{ admin_route('admin.surveys.duplicate', ['id' => $survey->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="eg-icon-button" aria-label="{{ $survey->title }}を複製"><i class="fas fa-copy" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</section>
</div>
@endsection
