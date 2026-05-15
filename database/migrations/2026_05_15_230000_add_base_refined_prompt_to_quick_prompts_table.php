<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quick_prompts', function (Blueprint $table) {
            $table->longText('base_refined_prompt')->nullable()->after('refined_prompt');
        });

        // 백필: 기존 row의 base 계산
        // 1) applied_suffix_ids가 있는 row → refined_prompt 끝에서 suffix body들을 역순으로 strip
        $rows = DB::table('quick_prompts')->whereNotNull('applied_suffix_ids')->get();
        foreach ($rows as $row) {
            $appliedIds = json_decode($row->applied_suffix_ids, true) ?: [];
            $base = (string) $row->refined_prompt;

            if (!empty($appliedIds)) {
                $suffixes = DB::table('prompt_suffixes')
                    ->whereIn('id', $appliedIds)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
                // 끝에서부터 제거하려면 적용 순서의 역순으로
                foreach ($suffixes->reverse()->values() as $sfx) {
                    $needle = "\n\n" . trim($sfx->body);
                    if (str_ends_with($base, $needle)) {
                        $base = substr($base, 0, -strlen($needle));
                    }
                }
            }

            DB::table('quick_prompts')
                ->where('id', $row->id)
                ->update(['base_refined_prompt' => $base]);
        }

        // 2) applied_suffix_ids가 없는 row → base = refined_prompt 그대로
        DB::table('quick_prompts')
            ->whereNull('base_refined_prompt')
            ->update(['base_refined_prompt' => DB::raw('refined_prompt')]);
    }

    public function down(): void
    {
        Schema::table('quick_prompts', function (Blueprint $table) {
            $table->dropColumn('base_refined_prompt');
        });
    }
};
