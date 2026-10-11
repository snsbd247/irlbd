<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department', 120)->nullable();
            $table->string('location', 150)->nullable();
            // full_time | part_time | contract | internship
            $table->string('employment_type', 20)->default('full_time');
            $table->unsignedSmallInteger('vacancies')->nullable();
            $table->string('salary', 120)->nullable();
            $table->date('deadline')->nullable();
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'deadline']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title')->nullable(); // snapshot
            $table->string('name', 120);
            $table->string('email');
            $table->string('phone', 30);
            $table->text('cover_letter')->nullable();
            // Stored on the PRIVATE disk, never inside the public web root.
            $table->string('cv_path');
            $table->string('cv_original_name');
            $table->string('cv_mime', 120);
            $table->unsignedInteger('cv_size');
            // new | shortlisted | interview | rejected | hired
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
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posts');
    }
};
