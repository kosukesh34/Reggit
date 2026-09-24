<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('price')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['project_id', 'is_active', 'sort_order']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('reservation_course_id')->nullable()->after('number_of_people')
                ->constrained('reservation_courses')->nullOnDelete();
            $table->string('course_name', 120)->nullable()->after('reservation_course_id');
            $table->unsignedInteger('course_price')->nullable()->after('course_name');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_course_id');
            $table->dropColumn(['course_name', 'course_price']);
        });
        Schema::dropIfExists('reservation_courses');
    }
};
