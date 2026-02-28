<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class ProfileController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = ShopSetting::getProfileFieldSettings($projectId);
        return view('admin.settings.profile', compact('settings'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $request->validate([
            'smaregi_fields' => 'required|array',
            'smaregi_fields.*.enabled' => 'nullable|boolean',
            'smaregi_fields.*.required' => 'nullable|boolean',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.key' => 'required|string|max:64|regex:/^[a-z0-9_]+$/',
            'custom_fields.*.label' => 'required|string|max:100',
            'custom_fields.*.required' => 'nullable|boolean',
        ]);

        $smaregi = [];
        foreach (ShopSetting::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $smaregi[$key] = [
                'label' => $label,
                'enabled' => filter_var($request->input("smaregi_fields.{$key}.enabled"), FILTER_VALIDATE_BOOLEAN),
                'required' => in_array($request->input("smaregi_fields.{$key}.required"), [1, '1', true], true),
            ];
        }

        $custom = [];
        $rawCustom = $request->input('custom_fields', []);
        if (is_array($rawCustom)) {
            foreach ($rawCustom as $i => $row) {
                $key = $row['key'] ?? ('custom_' . ($i + 1));
                $key = preg_replace('/[^a-z0-9_]/', '', $key) ?: ('custom_' . ($i + 1));
                $custom[] = [
                    'key' => $key,
                    'label' => $row['label'] ?? '項目' . ($i + 1),
                    'required' => (bool) ($row['required'] ?? false),
                ];
            }
        }

        ShopSetting::setProfileFieldSettings([
            'smaregi_fields' => $smaregi,
            'custom_fields' => $custom,
        ], $projectId);

        return redirect()->route('admin.settings.profile', $this->buildAdminRouteParams())
            ->with('success', 'プロフィール設定を更新しました');
    }
}
