<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_doc_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_doc_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->enum('change_type', ['user_add','user_edit','ai_integrate','ai_suggest','approved','rejected']);
            $table->longText('before_content')->nullable();
            $table->longText('after_content')->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->enum('approval_status', ['pending','approved','rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_doc_histories');
    }
};
