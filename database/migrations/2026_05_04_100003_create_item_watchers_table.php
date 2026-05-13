<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_watchers', function (Blueprint $table) {
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('subscribed_at')->useCurrent();

            $table->primary(['item_type', 'item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_watchers');
    }
};
