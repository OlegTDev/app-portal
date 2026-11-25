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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->nullable()->comment('AD sAMAccountName');
            $table->string('email')->nullable();
            $table->string('full_name')->nullable()->comment('ФИО');
            $table->string('position')->nullable()->comment('Должность');
            $table->string('rank')->nullable()->comment('Чин/звание');
            $table->string('phone')->nullable();
            $table->string('phone_additional')->nullable();
            $table->string('office')->nullable()->comment('Кабинет');
            $table->string('department_name')->nullable()->comment('Название отдела из AD');
            $table->string('organization_code', 10)->nullable();
            $table->string('photo_path')->nullable();
            $table->jsonb('ad_data')->nullable()->comment('Сырые данные из AD (memberof, dn)');
            $table->boolean('is_active')->default(true)->comment('Активен (не уволен)');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // Indexes
            $table->index('full_name');
            $table->index('phone');
            $table->index('organization_code');
            $table->index('is_active');
        });

        // Full-text search
        DB::statement("ALTER TABLE employees ADD COLUMN search_vector tsvector");
        DB::statement("CREATE INDEX employees_search_idx ON employees USING GIN (search_vector)");
        DB::statement("
            CREATE TRIGGER employees_search_update BEFORE INSERT OR UPDATE ON employees
            FOR EACH ROW EXECUTE FUNCTION
            tsvector_update_trigger('search_vector', 'pg_catalog.russian',
                'full_name', 'position', 'phone', 'phone_additional', 'email', 'department_name')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS employees_search_update ON employees");
        DB::statement("DROP INDEX IF EXISTS employees_search_idx");
        DB::statement("ALTER TABLE employees DROP COLUMN IF EXISTS search_vector");

        Schema::dropIfExists('employees');
    }
};
