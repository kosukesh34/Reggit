<input type="hidden" name="store_assignment" value="1">
<h3 class="settings-staff-permissions-title">アクセスできる支店</h3>
<p class="form-text text-muted">選択した支店の業務画面を利用できます。未選択の場合、支店の業務画面は利用できません。</p>
@foreach($assignableStores as $branch)
    <label class="checkbox-label"><input type="checkbox" name="store_ids[]" value="{{ $branch->id }}" @checked($member ? $member->stores->contains('id', $branch->id) : in_array($branch->id, old('store_ids', [])))> {{ $branch->name }}</label>
@endforeach
@if($canAssignManager)
    <div class="form-group">
        <label class="form-label" for="staff-role-{{ $member?->id ?? 'new' }}">権限種別</label>
        <select class="form-control" id="staff-role-{{ $member?->id ?? 'new' }}" name="role">
            <option value="staff" @selected(($member?->role ?? old('role', 'staff')) === 'staff')>スタッフ（操作権限を指定）</option>
            <option value="admin" @selected(($member?->role ?? old('role', 'staff')) === 'admin')>店舗管理者（担当支店の全操作・スタッフ管理）</option>
        </select>
        <p class="form-text text-muted">操作権限のチェックはスタッフに適用されます。店舗管理者は担当支店の全操作が可能です。</p>
    </div>
@endif
