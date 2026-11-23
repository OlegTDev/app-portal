<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            NestedSet::columns($table); // _lft, _rgt, parent_id
            $table->string('name', 100);
            $table->string('link', 500)->nullable();
            $table->string('icon', 50)->nullable()->comment('Иконка Ant Design');
            $table->string('target', 10)->nullable()->comment('_blank|_self');
            $table->string('position', 10)->default('main')->comment('main|left');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->index(['_lft', '_rgt', 'parent_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
