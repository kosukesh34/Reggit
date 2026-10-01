@extends('admin.layout')

@section('title', 'プロジェクト管理')
@section('page-title', 'プロジェクト管理')

@section('content')
<div class="card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-building"></i> プロジェクト一覧</h3>
        <a href="{{ route('onboarding.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 入力ホームから作成</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>プロジェクト名</th>
                <th>キー</th>
                <th>プラン</th>
                <th>利用店舗数</th>
                <th>状態</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
                <tr>
                    <td><span class="id-badge">{{ $project->id }}</span></td>
                    <td>{{ $project->name }}</td>
                    <td><code>{{ $project->project_key }}</code></td>
                    <td>
                        @php $planLabel = \App\Enums\ProjectPlan::getLabel($project->plan ?? 'normal'); @endphp
                        @if(($project->plan ?? 'normal') === 'premium')
                            <span class="badge badge-warning">{{ $planLabel }}</span>
                        @elseif(($project->plan ?? 'normal') === 'distribution')
                            <span class="badge badge-info">{{ $planLabel }}</span>
                        @else
                            <span class="badge badge-secondary">{{ $planLabel }}</span>
                        @endif
                    </td>
                    <td>{{ $project->active_stores_count }} / {{ $project->max_active_stores ?? 1 }}</td>
                    <td>
                        @if($project->isServiceAvailable())
                            <span class="badge badge-success">有効</span>
                        @else
                            <span class="badge badge-danger">{{ ['unpaid'=>'未払い・利用停止', 'cancelled'=>'解約'][$project->service_status] ?? '無効' }}</span>
                        @endif
                    </td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            @if($project->isServiceAvailable())
                                <a href="{{ route('admin.dashboard', ['project_key' => $project->project_key]) }}" class="btn btn-action btn-edit"><i class="fas fa-th-large"></i> ダッシュボード</a>
                                <a href="{{ route('liff.project', ['project_key' => $project->project_key]) }}" class="btn btn-action btn-outline" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt"></i> フロント</a>
                            @endif
                            <a href="{{ route('system-admin.projects.edit', $project->id) }}" class="btn btn-action btn-outline"><i class="fas fa-sliders-h"></i> 契約・連携設定</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-building"></i>
                        <p>プロジェクトがありません</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $projects->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
