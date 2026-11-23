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
        Schema::create('access_group_employee', function (Blueprint $table) {
            $table->uuid('access_group_id');
            $table->uuid('employee_id');
            $table->timestampTz('expired_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['access_group_id', 'employee_id']);

            $table->foreign('access_group_id')
                ->references('id')->on('access_groups')
                ->cascadeOnDelete();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();
        });

        Schema::create('access_group_ad_groups', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('access_group_id');
            $table->string('group_dn', 200)->comment('DN группы в AD');
            $table->string('group_name', 100)->comment('Название группы для отображения');
            $table->timestampTz('expired_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('access_group_id')
                ->references('id')->on('access_groups')
                ->cascadeOnDelete();
            $table->index('group_dn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_group_ad_groups');
        Schema::dropIfExists('access_group_employee');
    }
};
