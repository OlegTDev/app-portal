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
        Schema::create('tests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 250);
            $table->text('description')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('expired_at');
            $table->smallInteger('attempts_limit')->nullable()->comment('Лимит попыток');
            $table->smallInteger('questions_count')->default(0);
            $table->smallInteger('time_limit_minutes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });

        Schema::create('test_questions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('test_id');
            $table->text('question_text');
            $table->smallInteger('question_type')->default(1)->comment('1: single, 2: multiple');
            $table->string('attach_file_path', 200)->nullable();
            $table->smallInteger('weight')->default(1);
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('test_id')
                ->references('id')->on('tests')
                ->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });

        Schema::create('test_answers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('question_id');
            $table->text('answer_text');
            $table->string('attach_file_path', 200)->nullable();
            $table->smallInteger('weight')->default(1)->comment('Вес правильного ответа');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('question_id')
                ->references('id')->on('test_questions')
                ->cascadeOnDelete();
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('test_id');
            $table->uuid('employee_id');
            $table->string('organization_code', 10);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('test_id')
                ->references('id')->on('tests')
                ->cascadeOnDelete();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();
            $table->foreign('organization_code')
                ->references('code')->on('organizations')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_questions');
        Schema::dropIfExists('tests');
    }
};
