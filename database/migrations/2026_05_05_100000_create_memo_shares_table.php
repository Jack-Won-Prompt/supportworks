<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memo_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_to')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['memo_id', 'shared_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_shares');
    }
};
