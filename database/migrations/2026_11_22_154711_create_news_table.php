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
        Schema::create('news', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('title', 500);
            $table->text('summary')->nullable()->comment('Краткое описание');
            $table->text('body')->comment('Основной текст');
            $table->uuid('category_id')->nullable();
            $table->string('organization_code', 10)->nullable();
            $table->string('department_name', 500)->nullable()->comment('Отдел (автор материала)');
            $table->date('published_at')->comment('Начало публикации');
            $table->date('expired_at')->comment('Окончание публикации');
            $table->timestampTz('pinned_at')->nullable()->comment('Закреплена до');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('on_main_page')->default(false)->comment('Новость дня на главной');
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('visits_count')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->nullOnDelete();
            $table->foreign('organization_code')
                ->references('code')->on('organizations')
                ->nullOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')->on('employees')
                ->nullOnDelete();

            $table->index('is_enabled');
            $table->index('published_at');
            $table->index('organization_code');
        });

        // Full-text search
        DB::statement("ALTER TABLE news ADD COLUMN search_vector tsvector");
        DB::statement("CREATE INDEX news_search_idx ON news USING GIN (search_vector)");
        DB::statement("
            CREATE TRIGGER news_search_update BEFORE INSERT OR UPDATE ON news
            FOR EACH ROW EXECUTE FUNCTION
            tsvector_update_trigger('search_vector', 'pg_catalog.russian', 'title', 'summary', 'body')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS news_search_update ON news");
        DB::statement("DROP INDEX IF EXISTS news_search_idx");
        DB::statement("ALTER TABLE news DROP COLUMN IF EXISTS search_vector");

        Schema::dropIfExists('news');
    }
};
