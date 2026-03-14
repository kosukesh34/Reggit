<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberTag;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MemberTagSampleSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        if ($project === null) {
            $this->command->warn('プロジェクトが存在しません。スキップします。');

            return;
        }

        $tagDefs = [
            ['name' => 'VIP', 'color' => '#7c3aed'],
            ['name' => 'リピーター', 'color' => '#0891b2'],
            ['name' => '要フォロー', 'color' => '#dc2626'],
            ['name' => '新規', 'color' => '#16a34a'],
            ['name' => '休眠', 'color' => '#ca8a04'],
        ];

        $tags = [];
        foreach ($tagDefs as $def) {
            $tag = MemberTag::firstOrCreate(
                ['project_id' => $project->id, 'name' => $def['name']],
                ['color' => $def['color']]
            );
            $tags[$def['name']] = $tag;
            $this->command->info("タグ: {$tag->name}");
        }

        $members = Member::where('project_id', $project->id)->take(3)->get();
        foreach ($members as $i => $member) {
            if ($i === 0) {
                $member->tags()->syncWithoutDetaching([$tags['VIP']->id]);
                if ($member->memo === null) {
                    $member->update(['memo' => '常連のお客様。来店時は丁寧に対応すること。']);
                }
                $this->command->info("会員#{$member->id} → VIPタグ付与");
            } elseif ($i === 1) {
                $member->tags()->syncWithoutDetaching([$tags['リピーター']->id]);
                $this->command->info("会員#{$member->id} → リピータータグ付与");
            } elseif ($i === 2) {
                $member->tags()->syncWithoutDetaching([$tags['新規']->id]);
                $this->command->info("会員#{$member->id} → 新規タグ付与");
            }
        }

        $this->command->info('サンプルデータ投入完了');
    }
}
