<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->boolean('out_of_scope')->default(false)->after('approval_status');
            $table->text('scope_reason')->nullable()->after('out_of_scope');
            $table->foreignId('duplicate_of_id')->nullable()->after('scope_reason')
                  ->constrained('requirements')->nullOnDelete();
            $table->text('duplicate_reason')->nullable()->after('duplicate_of_id');
            $table->index(['project_id', 'out_of_scope']);
            $table->index(['project_id', 'duplicate_of_id']);
        });
    }

    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of_id']);
            $table->dropIndex(['project_id', 'out_of_scope']);
            $table->dropIndex(['project_id', 'duplicate_of_id']);
            $table->dropColumn(['out_of_scope', 'scope_reason', 'duplicate_of_id', 'duplicate_reason']);
        });
    }
};
