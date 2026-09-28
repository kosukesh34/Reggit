@if(isset($availableStores) && isset($currentProject) && !request()->routeIs('system-admin.*'))
@php $allStoresSelected = $canManageStores && session('admin_store.'.$currentProject->id) === 'all'; @endphp
<details class="admin-store-switcher">
    <summary><i class="fas fa-store" aria-hidden="true"></i><span>{{ $allStoresSelected ? '全店舗' : ($selectedStore?->name ?? ($canManageStores ? '全店舗' : '店舗を選択')) }}</span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
    <div class="admin-store-switcher-panel">
        <form method="POST" action="{{ route('admin.stores.select', ['project_key' => $currentProject->project_key]) }}">@csrf
            <label for="admin-store-select">操作する店舗</label>
            <select id="admin-store-select" name="store_id" class="form-control" onchange="this.form.requestSubmit()">
                @if($canManageStores)<option value="all" @selected($allStoresSelected || !$selectedStore)>全店舗</option>@elseif(!$selectedStore)<option value="" disabled selected>店舗を選択してください</option>@endif
                @foreach($availableStores as $store)<option value="{{ $store->id }}" @selected(!$allStoresSelected && $selectedStore?->id === $store->id)>{{ $store->name }}</option>@endforeach
            </select>
        </form>
    </div>
</details>
@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const switcher = document.querySelector('.admin-store-switcher');
    if (switcher?.open && !switcher.contains(event.target)) {
        switcher.open = false;
    }
});
</script>
@endpush
@endif
