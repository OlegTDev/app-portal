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
        Schema::create('mentor_ways', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 200);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('mentor_posts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('mentor_way_id');
            $table->string('organization_code', 10);
            $table->string('title', 500);
            $table->text('body');
            $table->uuid('created_by')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('visits_count')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('mentor_way_id')
                ->references('id')->on('mentor_ways')
                ->cascadeOnDelete();
            $table->foreign('organization_code')
                ->references('code')->on('organizations')
                ->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });

        Schema::create('mentor_role_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('role_name', 100);
            $table->uuid('employee_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_role_assignments');
        Schema::dropIfExists('mentor_posts');
        Schema::dropIfExists('mentor_ways');
    }
};
