<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) SR 대상 테이블 — SR 접수를 묶는 상위 항목 (프로젝트 연결은 선택)
        Schema::create('sr_targets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('company_group_id')->nullable();
            $table->timestamps();
        });

        // 2) project_maintenances: sr_target_id 추가 + project_id nullable화
        Schema::table('project_maintenances', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::table('project_maintenances', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
            $table->foreignId('sr_target_id')->nullable()->after('project_id')
                  ->constrained('sr_targets')->cascadeOnDelete();
        });
        Schema::table('project_maintenances', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        // 3) maintenance_files: sr_target_id 추가 + project_id nullable화
        Schema::table('maintenance_files', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::table('maintenance_files', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
            $table->foreignId('sr_target_id')->nullable()->after('project_id')
                  ->constrained('sr_targets')->nullOnDelete();
        });
        Schema::table('maintenance_files', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        // 4) maintenance_file_categories: sr_target_id 추가 + project_id nullable화
        Schema::table('maintenance_file_categories', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::table('maintenance_file_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
            $table->foreignId('sr_target_id')->nullable()->after('project_id')
                  ->constrained('sr_targets')->cascadeOnDelete();
        });
        Schema::table('maintenance_file_categories', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        // 5) 기존 데이터 이관 — 프로젝트별로 SR 대상 1건 생성 후 연결
        $projectIds = DB::table('project_maintenances')->whereNotNull('project_id')->distinct()->pluck('project_id')
            ->merge(DB::table('maintenance_files')->whereNotNull('project_id')->distinct()->pluck('project_id'))
            ->merge(DB::table('maintenance_file_categories')->whereNotNull('project_id')->distinct()->pluck('project_id'))
            ->unique();

        foreach ($projectIds as $pid) {
            $project = DB::table('projects')->find($pid);
            if (!$project) {
                continue;
            }

            $creatorId = DB::table('project_maintenances')->where('project_id', $pid)->value('user_id')
                ?? DB::table('maintenance_files')->where('project_id', $pid)->value('uploaded_by')
                ?? DB::table('users')->min('id');

            $srId = DB::table('sr_targets')->insertGetId([
                'title'            => $project->name,
                'project_id'       => $pid,
                'created_by'       => $creatorId,
                'company_group_id' => $project->company_group_id ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('project_maintenances')->where('project_id', $pid)->update(['sr_target_id' => $srId]);
            DB::table('maintenance_files')->where('project_id', $pid)->update(['sr_target_id' => $srId]);
            DB::table('maintenance_file_categories')->where('project_id', $pid)->update(['sr_target_id' => $srId]);
        }
    }

    public function down(): void
    {
        foreach (['project_maintenances', 'maintenance_files', 'maintenance_file_categories'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropForeign(['sr_target_id']);
                $table->dropColumn('sr_target_id');
            });
        }

        Schema::dropIfExists('sr_targets');
    }
};
