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
        Schema::create('portal_users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('employee_id')->unique();
            $table->string('password')->nullable()->comment('bcrypt hash');
            $table->rememberToken();
            $table->string('default_organization_code', 10)->nullable();
            $table->string('current_organization_code', 10)->nullable();
            $table->boolean('blocked')->default(false);
            $table->timestampTz('last_login_at')->nullable();
            $table->string('last_action_url')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();
            $table->foreign('default_organization_code')
                ->references('code')->on('organizations')
                ->nullOnDelete();
            $table->foreign('current_organization_code')
                ->references('code')->on('organizations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_users');
    }
};
