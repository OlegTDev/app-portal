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
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('organization_code', 10);
            $table->string('department_index', 5)->comment('Индекс отдела');
            $table->string('name');
            $table->string('short_name', 50)->nullable();
            $table->boolean('use_card')->default(false)->comment('Использовать карточку отдела');
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('organization_code')
                ->references('code')->on('organizations')
                ->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });

        Schema::create('department_employee', function (Blueprint $table) {
            $table->uuid('department_id');
            $table->uuid('employee_id');
            $table->string('position')->nullable()->comment('Должность в отделе');
            $table->string('rank')->nullable()->comment('Чин в отделе');
            $table->string('phone')->nullable()->comment('Телефон (если отличается от основного)');
            $table->text('responsibilities')->nullable()->comment('Обязанности');
            $table->integer('sort_order')->default(0);
            $table->string('photo_path')->nullable()->comment('Фото для карточки отдела');
            $table->primary(['department_id', 'employee_id']);

            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->cascadeOnDelete();
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
        Schema::dropIfExists('department_employee');
        Schema::dropIfExists('departments');
    }
};
