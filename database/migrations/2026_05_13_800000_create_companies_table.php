<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('company');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->index('company_id');
        });

        // 기존 User.company 문자열 → companies 테이블로 자동 매핑
        $existing = DB::table('users')
            ->whereNotNull('company')
            ->whereRaw("TRIM(company) NOT IN ('', '-')")
            ->pluck('company')
            ->map(fn($v) => trim((string) $v))
            ->unique()
            ->values();

        foreach ($existing as $name) {
            DB::table('companies')->insertOrIgnore([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $map = DB::table('companies')->pluck('id', 'name');
        foreach ($map as $name => $cid) {
            DB::table('users')
                ->whereRaw('TRIM(company) = ?', [$name])
                ->update(['company_id' => $cid]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
        Schema::dropIfExists('companies');
    }
};
