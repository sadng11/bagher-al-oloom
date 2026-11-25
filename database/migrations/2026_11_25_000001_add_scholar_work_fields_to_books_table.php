<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('section', 32)->default('writer')->after('writer_id')->index();
            $table->string('author_name', 500)->nullable()->default(null)->after('section');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('writer_id')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['section']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['author_name', 'section']);
        });

        if (! DB::table('books')->whereNull('writer_id')->exists()) {
            Schema::table('books', function (Blueprint $table) {
                $table->unsignedBigInteger('writer_id')->nullable(false)->change();
            });
        }
    }
};
