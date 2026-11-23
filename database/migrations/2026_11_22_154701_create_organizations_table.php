<?php

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
        Schema::create('organizations', function (Blueprint $table) {
            $table->string('code', 10)->primary()->comment('Код НО: 8600, 8601, ...');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('parent_code', 10)->nullable()->comment('Реорганизована в ...');
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable()->comment('Историческая справка');
            $table->string('upload_images_path')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('ended_at')->nullable()->comment('Дата окончания деятельности');

            $table->foreign('parent_code')
                ->references('code')->on('organizations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
