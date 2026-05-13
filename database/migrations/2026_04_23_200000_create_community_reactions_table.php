<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 10); // like | heart | laugh | wow | sad | fire
            $table->timestamps();
            $table->unique(['post_id', 'user_id']); // 유저당 포스트에 이모지 하나만
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reactions');
    }
};
