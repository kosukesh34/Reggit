<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('color', 7)->default('#6b7280');
            $table->timestamps();
            $table->index('project_id');
        });

        Schema::create('member_tag_members', function (Blueprint $table) {
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('member_tag_id')->constrained('member_tags')->cascadeOnDelete();
            $table->primary(['member_id', 'member_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tag_members');
        Schema::dropIfExists('member_tags');
    }
};
