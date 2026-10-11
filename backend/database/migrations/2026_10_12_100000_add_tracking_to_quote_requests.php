<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            // Public tracking number, e.g. IRQ-2026-00012.
            $table->string('reference', 20)->nullable()->unique()->after('id');
            // Short message shown to the customer on the tracking page (admin_note stays internal).
            $table->string('customer_note', 1000)->nullable()->after('admin_note');
            $table->timestamp('status_changed_at')->nullable()->after('customer_note');
        });

        DB::table('quote_requests')->whereNull('reference')->orderBy('id')->get(['id', 'created_at'])
            ->each(fn ($row) => DB::table('quote_requests')->where('id', $row->id)->update([
                'reference' => sprintf('IRQ-%s-%05d', substr((string) $row->created_at, 0, 4) ?: date('Y'), $row->id),
            ]));
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'customer_note', 'status_changed_at']);
        });
    }
};
