<?php

namespace App\Models;

use App\Notifications\AdminPasswordReset;
use App\Support\AdminLoginIpAllowlist;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Passkeys\Passkeys;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable implements PasskeyUser
{
    use HasApiTokens, HasFactory, \Illuminate\Notifications\Notifiable, PasskeyAuthenticatable;

    public const ROLE_SYSTEM_ADMIN = 'system_admin';

    public const ROLE_PROJECT_ADMIN = 'project_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    /** ロール階層（数値が大きいほど権限が強い） */
    public const ROLE_HIERARCHY = [
        self::ROLE_STAFF => 1,
        self::ROLE_ADMIN => 2,
        self::ROLE_PROJECT_ADMIN => 3,
        self::ROLE_SYSTEM_ADMIN => 4,
    ];

    /** 権限の表示名（管理画面・一覧で統一して表示） */
    public const ROLE_LABELS = [
        self::ROLE_SYSTEM_ADMIN => 'システム管理者（全プロジェクト操作・アカウント管理可）',
        self::ROLE_PROJECT_ADMIN => 'プロジェクト管理者（担当プロジェクトのみ操作可）',
        self::ROLE_ADMIN => '店舗管理者（担当店舗のみ操作可）',
        self::ROLE_STAFF => 'スタッフ（カスタム権限）',
    ];

    /** 短い表示名（ヘッダー等） */
    public const ROLE_LABELS_SHORT = [
        self::ROLE_SYSTEM_ADMIN => 'システム管理者',
        self::ROLE_PROJECT_ADMIN => 'プロジェクト管理者',
        self::ROLE_ADMIN => '店舗管理者',
        self::ROLE_STAFF => 'スタッフ',
    ];

    /**
     * 旧データ互換のロールエイリアス。
     *
     * 過去バージョン由来の値が残っていても、判定を壊さないように正規化する。
     *
     * @var array<string, string>
     */
    private const LEGACY_ROLE_ALIASES = [
        'system' => self::ROLE_SYSTEM_ADMIN,
        'sys_admin' => self::ROLE_SYSTEM_ADMIN,
        'system-admin' => self::ROLE_SYSTEM_ADMIN,
        'store_admin' => self::ROLE_ADMIN,
    ];

    private static function normalizeRoleValue(?string $role): string
    {
        $raw = strtolower(trim((string) $role));
        if ($raw === '') {
            return self::ROLE_STAFF;
        }

        return self::LEGACY_ROLE_ALIASES[$raw] ?? $raw;
    }

    public static function getRoleLabel(string $role): string
    {
        $normalized = self::normalizeRoleValue($role);

        return self::ROLE_LABELS[$normalized] ?? $role;
    }

    public static function getRoleLabelShort(string $role): string
    {
        $normalized = self::normalizeRoleValue($role);

        return self::ROLE_LABELS_SHORT[$normalized] ?? $role;
    }

    /** 指定ロールの階層レベルを返す（static） */
    public static function getRoleLevelFor(string $role): int
    {
        $normalized = self::normalizeRoleValue($role);

        return self::ROLE_HIERARCHY[$normalized] ?? 0;
    }

    protected $fillable = [
        'login_id',
        'email',
        'password',
        'name',
        'role',
        'project_id',
        'created_by_id',
        'login_allowed_ips',
        'login_notify_by_email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'password' => 'hashed',
        'login_notify_by_email' => 'boolean',
    ];

    public function allowsLoginFromIp(?string $ip): bool
    {
        return AdminLoginIpAllowlist::allows($ip, AdminLoginIpAllowlist::parse($this->login_allowed_ips));
    }

    protected static function booted(): void
    {
        static::created(function (self $admin) {
            if ($admin->project_id !== null) {
                $stores = Store::where('project_id', $admin->project_id)->limit(2)->get();
                if ($stores->count() === 1) {
                    $store = $stores->first();
                    $admin->stores()->syncWithoutDetaching([$store->id => ['project_id' => $store->project_id]]);
                }
            }
        });
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminPasswordReset($token));
    }

    // ── リレーション ──

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'admin_user_stores')->withPivot('project_id')->withTimestamps();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** 担当プロジェクト（複数可） */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'admin_user_projects')
            ->withTimestamps();
    }

    /** スタッフ権限 */
    public function permissions(): HasMany
    {
        return $this->hasMany(AdminUserPermission::class);
    }

    /** 作成者 */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by_id');
    }

    // ── プロジェクトアクセス ──

    /** アクセス可能なプロジェクトID一覧 */
    public function getAllowedProjectIds(): array
    {
        if ($this->isSystemAdmin()) {
            return Project::where('is_active', true)->pluck('id')->all();
        }
        $pivotIds = $this->projects()->pluck('projects.id')->all();
        if ($pivotIds !== []) {
            return $pivotIds;
        }
        if ($this->project_id) {
            return [$this->project_id];
        }

        return [];
    }

    /** 指定プロジェクトにアクセス可能か */
    public function canAccessProject(int $projectId): bool
    {
        return in_array($projectId, $this->getAllowedProjectIds(), true);
    }

    public static function resolveCurrentProjectId(self $admin, ?int $sessionProjectId, $requestProjectId): int
    {
        $allowedIds = $admin->getAllowedProjectIds();
        $sessionId = (int) $sessionProjectId;

        if ($admin->isSystemAdmin()) {
            $projectId = $requestProjectId ?? ($sessionId ?: null);
            if ($projectId && in_array((int) $projectId, $allowedIds, true)) {
                return (int) $projectId;
            }
            $fallback = Project::where('is_active', true)->min('id');
            if ($fallback === null) {
                throw new \RuntimeException('有効な店舗がありません');
            }

            return (int) $fallback;
        }

        if ($sessionId && $admin->canAccessProject($sessionId)) {
            return $sessionId;
        }
        if ($allowedIds !== []) {
            return (int) $allowedIds[0];
        }
        if ($admin->project_id) {
            return (int) $admin->project_id;
        }

        return 1;
    }

    /** @return HasMany<Passkey, $this> */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkeys::passkeyModel(), 'user_id');
    }

    public function getPasskeyDisplayName(): string
    {
        $name = trim((string) ($this->name ?? ''));

        return $name !== '' ? $name : $this->getPasskeyUsername();
    }

    public function getPasskeyUsername(): string
    {
        return (string) $this->login_id;
    }

    // ── ロール判定 ──

    public function isSystemAdmin(): bool
    {
        return self::normalizeRoleValue($this->role) === self::ROLE_SYSTEM_ADMIN;
    }

    public function isProjectAdmin(): bool
    {
        return self::normalizeRoleValue($this->role) === self::ROLE_PROJECT_ADMIN;
    }

    public function isAdmin(): bool
    {
        return self::normalizeRoleValue($this->role) === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return self::normalizeRoleValue($this->role) === self::ROLE_STAFF;
    }

    /** 店舗管理画面（/admin/*）にアクセス可能か。全ロール許可。 */
    public function canAccessStoreAdmin(): bool
    {
        return in_array(self::normalizeRoleValue($this->role), [
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_PROJECT_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
        ], true);
    }

    // ── 権限（パーミッション） ──

    /** キャッシュ用 */
    private ?array $cachedPermissions = null;

    /** スタッフの保有する権限キー一覧を取得（キャッシュ） */
    public function getPermissionKeys(): array
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }
        if ($this->isStaff() === false) {
            return $this->cachedPermissions = array_keys(AdminUserPermission::ALL_PERMISSIONS);
        }

        return $this->cachedPermissions = $this->permissions()->pluck('permission')->all();
    }

    /** 指定権限を保有しているか。staff 以外は全権限を持つとみなす。 */
    public function hasPermission(string $permission): bool
    {
        if ($this->isStaff() === false) {
            return true;
        }

        return in_array($permission, $this->getPermissionKeys(), true);
    }

    /** 複数権限のうちいずれかを持つか */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->hasPermission($p)) {
                return true;
            }
        }

        return false;
    }

    /** 権限を同期する（スタッフ用） */
    public function syncPermissions(array $permissionKeys): void
    {
        $valid = array_intersect($permissionKeys, array_keys(AdminUserPermission::ALL_PERMISSIONS));
        $this->permissions()->delete();
        foreach ($valid as $key) {
            $this->permissions()->create(['permission' => $key]);
        }
        $this->cachedPermissions = null;
    }

    // ── アカウント作成権限 ──

    /** 自分の階層レベル */
    public function getRoleLevel(): int
    {
        return self::getRoleLevelFor($this->role);
    }

    /** 指定ロールのアカウントを作成できるか */
    public function canCreateRole(string $targetRole): bool
    {
        if ($this->isStaff()) {
            return false;
        }
        $myLevel = $this->getRoleLevel();
        $targetLevel = self::ROLE_HIERARCHY[$targetRole] ?? 0;

        // system_admin: 全ロール作成可
        if ($this->isSystemAdmin()) {
            return true;
        }
        // project_admin: admin, staff を作成可
        if ($this->isProjectAdmin()) {
            return in_array($targetRole, [self::ROLE_ADMIN, self::ROLE_STAFF], true);
        }
        // admin: staff のみ作成可
        if ($this->isAdmin()) {
            return $targetRole === self::ROLE_STAFF;
        }

        return false;
    }

    /** 作成可能なロール一覧を返す */
    public function getCreatableRoles(): array
    {
        return array_filter(
            array_keys(self::ROLE_LABELS),
            fn (string $role) => $this->canCreateRole($role)
        );
    }

    /** 指定ユーザーを編集できるか */
    public function canEditUser(self $target): bool
    {
        if ($this->isStaff()) {
            return false;
        }
        if ($this->isSystemAdmin()) {
            return true;
        }
        $targetLevel = self::ROLE_HIERARCHY[$target->role] ?? 0;
        $myLevel = $this->getRoleLevel();

        return $myLevel > $targetLevel;
    }

    // ── スコープ ──

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeStaff($query)
    {
        return $query->where('role', self::ROLE_STAFF);
    }
}
