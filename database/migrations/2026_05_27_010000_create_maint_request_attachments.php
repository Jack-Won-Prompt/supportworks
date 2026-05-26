<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_request_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')
              ->constrained('maint_requests')
              ->cascadeOnDelete();
            $t->foreignId('uploaded_by')->nullable()
              ->constrained('users')
              ->nullOnDelete();
            $t->string('original_name', 255);
            $t->string('disk', 32)->default('local');
            $t->string('path', 500);
            $t->unsignedBigInteger('size');
            $t->string('mime', 100)->nullable();
            $t->timestamps();

            $t->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_request_attachments');
    }
};
