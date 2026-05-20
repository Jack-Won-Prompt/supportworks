<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Preview\LayoutPreviewBuilder;
use Illuminate\Http\Response;

class PreviewController extends Controller
{
    public function __construct(private LayoutPreviewBuilder $previewBuilder) {}

    public function svg(Task $task): Response
    {
        $options = $task->options;
        if (! $options) {
            abort(404, 'no options yet');
        }
        $svg = $this->previewBuilder->build($options);
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
