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
        Schema::create('automation_routine_downloads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_routine_id');
            $table->string('filename', 500);
            $table->unsignedBigInteger('employee_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('automation_routine_id')
                ->references('id')->on('automation_routines')
                ->cascadeOnDelete();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();
        });

        Schema::create('automation_routine_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_routine_id');
            $table->smallInteger('rate')->comment('1-5');
            $table->unsignedBigInteger('employee_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('automation_routine_id')
                ->references('id')->on('automation_routines')
                ->cascadeOnDelete();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();

            $table->unique(['automation_routine_id', 'employee_id']);
        });

        Schema::create('automation_routine_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_routine_id');
            $table->string('result', 30)->comment('reject|accepted');
            $table->unsignedBigInteger('employee_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('automation_routine_id')
                ->references('id')->on('automation_routines')
                ->cascadeOnDelete();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();

            $table->unique(['automation_routine_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_routine_feedbacks');
        Schema::dropIfExists('automation_routine_rates');
        Schema::dropIfExists('automation_routine_downloads');
    }
};
