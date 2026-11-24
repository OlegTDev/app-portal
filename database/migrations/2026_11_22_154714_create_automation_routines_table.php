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
        Schema::create('automation_routines', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('region_mail_account', 300)->nullable()->comment('Реквизиты письма Управления');
            $table->string('owner_department', 250)->comment('Владелец (структурное подразделение)');
            $table->string('ftp_path', 250)->nullable();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_routines');
    }
};
