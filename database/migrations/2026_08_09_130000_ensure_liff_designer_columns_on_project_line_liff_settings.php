<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ローカル等で project_line_liff_settings が旧 liff_design のみのスキーマに
 * ドリフトしていると、Designer 保存が Unknown column で失敗する。
 * 不足カラムを追加し、可能な範囲で liff_design から初期値を移す。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_line_liff_settings')) {
            return;
        }

        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('project_line_liff_settings', 'liff_builder_blocks')) {
                $table->json('liff_builder_blocks')->nullable();
            }
            if (! Schema::hasColumn('project_line_liff_settings', 'liff_tab_layouts')) {
                $table->json('liff_tab_layouts')->nullable();
            }
            if (! Schema::hasColumn('project_line_liff_settings', 'liff_tab_settings')) {
                $table->json('liff_tab_settings')->nullable();
            }
            if (! Schema::hasColumn('project_line_liff_settings', 'liff_detail_layouts')) {
                $table->json('liff_detail_layouts')->nullable();
            }
            if (! Schema::hasColumn('project_line_liff_settings', 'liff_saved_screens')) {
                $table->json('liff_saved_screens')->nullable();
            }
            if (! Schema::hasColumn('project_line_liff_settings', 'liff_custom_css')) {
                $table->text('liff_custom_css')->nullable();
            }
            if (! Schema::hasColumn('project_line_liff_settings', 'frontend_menu_items')) {
                $table->json('frontend_menu_items')->nullable();
            }
        });

        if (! Schema::hasColumn('project_line_liff_settings', 'liff_design')) {
            return;
        }

        $rows = DB::table('project_line_liff_settings')->select([
            'project_id',
            'liff_design',
            'liff_tab_layouts',
            'liff_tab_settings',
            'liff_detail_layouts',
            'liff_custom_css',
        ])->get();

        foreach ($rows as $row) {
            $design = json_decode((string) ($row->liff_design ?? ''), true);
            if (! is_array($design)) {
                continue;
            }

            $patch = [];

            if (($row->liff_tab_settings === null || $row->liff_tab_settings === '')
                && isset($design['theme'])
                && is_array($design['theme'])
            ) {
                $patch['liff_tab_settings'] = json_encode(['_global' => $design['theme']], JSON_UNESCAPED_UNICODE);
            }

            if (($row->liff_detail_layouts === null || $row->liff_detail_layouts === '')
                && isset($design['detailLayouts'])
                && is_array($design['detailLayouts'])
            ) {
                $patch['liff_detail_layouts'] = json_encode($design['detailLayouts'], JSON_UNESCAPED_UNICODE);
            }

            if (($row->liff_custom_css === null || $row->liff_custom_css === '')
                && isset($design['customCss'])
                && is_string($design['customCss'])
            ) {
                $patch['liff_custom_css'] = $design['customCss'];
            }

            if (($row->liff_tab_layouts === null || $row->liff_tab_layouts === '')
                && isset($design['screens'])
                && is_array($design['screens'])
                && $this->isAssocScreenMap($design['screens'])
            ) {
                $layouts = [];
                foreach ($design['screens'] as $tab => $blocks) {
                    if (! is_string($tab) || $tab === '') {
                        continue;
                    }
                    if (is_array($blocks) && array_is_list($blocks)) {
                        $layouts[$tab] = $blocks;
                    } elseif (is_array($blocks) && isset($blocks['blocks']) && is_array($blocks['blocks'])) {
                        $layouts[$tab] = $blocks['blocks'];
                    }
                }
                if ($layouts !== []) {
                    $patch['liff_tab_layouts'] = json_encode($layouts, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($patch !== []) {
                DB::table('project_line_liff_settings')
                    ->where('project_id', $row->project_id)
                    ->update($patch);
            }
        }
    }

    public function down(): void
    {
        // ドリフト修復用。既存環境の列を消さない。
    }

    /**
     * @param  array<mixed>  $screens
     */
    private function isAssocScreenMap(array $screens): bool
    {
        if ($screens === []) {
            return false;
        }

        return ! array_is_list($screens);
    }
};
