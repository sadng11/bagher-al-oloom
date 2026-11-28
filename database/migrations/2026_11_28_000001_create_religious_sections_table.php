<?php

use App\Models\ReligiousSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('religious_sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('title', 120);
            $table->string('group_type', 20)->index();
            $table->unsignedTinyInteger('sort_order')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        $rows = array_map(static function (array $section) use ($now): array {
            return [
                'slug' => $section['slug'],
                'title' => $section['title'],
                'group_type' => $section['group_type'],
                'sort_order' => $section['sort_order'],
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, ReligiousSection::defaultSections());

        DB::table('religious_sections')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('religious_sections');
    }
};
