<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('religious_practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religious_section_id')
                ->constrained('religious_sections')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number')->nullable()->index();
            $table->string('title', 500);
            $table->longText('content');
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();

            $table->index(
                ['religious_section_id', 'day_number', 'published', 'sort_order'],
                'rp_section_day_pub_sort_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('religious_practices');
    }
};
