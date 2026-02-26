<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminUserPermission extends Model
{
    protected $fillable = [
        'admin_user_id',
        'permission',
    ];

    /** 定義済み権限キー一覧 */
    public const ALL_PERMISSIONS = [
        'dashboard.view'       => 'ダッシュボード閲覧',
        'orders.view'          => '注文の閲覧',
        'orders.update'        => '注文ステータスの更新',
        'products.view'        => '商品の閲覧',
        'products.manage'      => '商品の作成・編集・削除',
        'members.view'         => '会員の閲覧',
        'members.manage'       => '会員の編集',
        'reservations.view'    => '予約の閲覧',
        'reservations.update'  => '予約ステータスの更新',
        'queue.view'           => '順番待ちの閲覧',
        'queue.update'         => '順番待ちステータスの更新',
        'stamps.view'          => 'スタンプの閲覧',
        'stamps.manage'        => 'スタンプの作成・編集・削除',
        'coupons.view'         => 'クーポンの閲覧',
        'coupons.manage'       => 'クーポンの作成・編集・削除',
        'announcements.view'   => 'お知らせの閲覧',
        'announcements.manage' => 'お知らせの作成・編集・削除',
        'analytics.view'       => '分析の閲覧',
        'audit_logs.view'      => '監査ログの閲覧',
        'gallery.view'         => 'ギャラリーの閲覧',
        'gallery.manage'       => 'ギャラリーの作成・編集・削除',
        'liff_designer.manage' => 'LIFFデザインの編集',
        'settings.view'        => '設定の閲覧',
        'settings.manage'      => '設定の変更',
    ];

    /** 権限をカテゴリでグループ化 */
    public const PERMISSION_GROUPS = [
        'ダッシュボード' => ['dashboard.view'],
        '注文管理'       => ['orders.view', 'orders.update'],
        '商品管理'       => ['products.view', 'products.manage'],
        '会員管理'       => ['members.view', 'members.manage'],
        '予約管理'       => ['reservations.view', 'reservations.update'],
        '順番待ち'       => ['queue.view', 'queue.update'],
        'スタンプ'       => ['stamps.view', 'stamps.manage'],
        'クーポン'       => ['coupons.view', 'coupons.manage'],
        'お知らせ'       => ['announcements.view', 'announcements.manage'],
        '分析'           => ['analytics.view'],
        'セキュリティ'   => ['audit_logs.view'],
        'ギャラリー'     => ['gallery.view', 'gallery.manage'],
        'LIFFデザイン'   => ['liff_designer.manage'],
        '設定'           => ['settings.view', 'settings.manage'],
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }
}
