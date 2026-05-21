<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_request_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('maint_requests')->cascadeOnDelete();
            $t->enum('note_type', ['colo', 'link'])->comment('colo=콜로 비고, link=링크 비고');
            $t->text('body');
            $t->timestamps();

            $t->index('request_id', 'idx_notes_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_request_notes');
    }
};
