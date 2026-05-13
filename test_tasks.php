<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// tasks 컬럼 중 group 관련만 확인
echo "=== tasks columns with 'group' ===\n";
foreach (\Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM tasks") as $c) {
    if (str_contains($c->Field, 'group') || str_contains($c->Field, 'project')) {
        echo "  {$c->Field}\n";
    }
}

// join으로 project 7의 tasks 조회
echo "\n=== Tasks for project_id=7 (via task_groups) ===\n";
$rows = \Illuminate\Support\Facades\DB::select("
    SELECT t.id, t.title, t.status, tg.project_id, tg.title as group_title
    FROM tasks t
    JOIN task_groups tg ON tg.id = t.task_group_id
    WHERE tg.project_id = 7
    LIMIT 10
");
echo "Count: " . count($rows) . "\n";
foreach ($rows as $r) {
    $r = (array)$r;
    echo "  [{$r['id']}] {$r['title']} ({$r['status']}) — group: {$r['group_title']}\n";
}
