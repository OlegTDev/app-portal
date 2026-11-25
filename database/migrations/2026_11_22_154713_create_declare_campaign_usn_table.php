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
        Schema::create('declare_campaign_usn', function (Blueprint $table) {
            $table->id();
            $table->string('year', 4);
            $table->date('date')->comment('Дата ввода');
            $table->date('deadline')->comment('Срок уплаты');
            $table->string('organization_code', 10);
            $table->integer('np_count')->comment('Количество НП (ед.)');
            $table->integer('np_reliable_count')->comment('НП, представившие верные Уведомления (ед.)');
            $table->integer('np_not_required_count')->comment('НП, которым Уведомление не требуется (ед.)');
            $table->decimal('accrued_amount', 15, 2)->nullable()->comment('Сумма начисленного налога, руб.');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('organization_code')
                ->references('code')->on('organizations')
                ->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('employees')
                ->nullOnDelete();

            $table->unique(['year', 'date', 'deadline', 'organization_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declare_campaign_usn');
    }
};
