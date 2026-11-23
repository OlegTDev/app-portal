<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_organization', function (Blueprint $table) {
            $table->uuid('employee_id');
            $table->string('organization_code', 10);
            $table->primary(['employee_id', 'organization_code']);

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
        Schema::dropIfExists('employee_organization');
    }
};
