<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot of the product name, kept even if the product is later renamed or deleted.
            $table->string('product_name', 200)->nullable();
            $table->string('name', 120);
            $table->string('company', 150)->nullable();
            $table->string('email');
            $table->string('phone', 30);
            $table->string('quantity', 60);
            $table->string('specification', 500)->nullable();
            $table->date('needed_by')->nullable();
            $table->text('message')->nullable();
            // new | quoted | ordered | closed
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
