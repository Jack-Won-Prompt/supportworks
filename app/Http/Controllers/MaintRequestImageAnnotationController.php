<?php

namespace App\Http\Controllers;

use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestImageAnnotation;
use Illuminate\Http\Request;

class MaintRequestImageAnnotationController extends Controller
{
    /**
     * 특정 SR + image_url 의 주석 목록.
     */
    public function index(Request $request, MaintRequest $maintRequest)
    {
        $request->validate(['image_url' => 'required|string|max:500']);

        $rows = MaintRequestImageAnnotation::with('user:id,name')
            ->where('maint_request_id', $maintRequest->id)
            ->where('image_url', $request->string('image_url'))
            ->orderBy('id')
            ->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'user_id'    => $a->user_id,
                'user_name'  => $a->user?->name,
                'shape'      => $a->shape,
                'color'      => $a->color,
                'payload'    => $a->payload,
                'created_at' => $a->created_at?->format('Y-m-d H:i'),
                'can_delete' => (auth()->id() === $a->user_id) || (auth()->user()?->isAdmin() ?? false),
            ]);

        return response()->json(['ok' => true, 'annotations' => $rows]);
    }

    public function store(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'image_url' => 'required|string|max:500',
            'shape'     => 'required|in:rect,circle,line,number,text',
            'color'     => 'nullable|string|max:16',
            'payload'   => 'required|array',
        ]);

        $a = MaintRequestImageAnnotation::create([
            'maint_request_id' => $maintRequest->id,
            'image_url'        => $data['image_url'],
            'user_id'          => auth()->id(),
            'shape'            => $data['shape'],
            'color'            => $data['color'] ?? '#ef4444',
            'payload'          => $data['payload'],
        ]);

        return response()->json([
            'ok' => true,
            'annotation' => [
                'id'         => $a->id,
                'user_id'    => $a->user_id,
                'user_name'  => auth()->user()?->name,
                'shape'      => $a->shape,
                'color'      => $a->color,
                'payload'    => $a->payload,
                'created_at' => $a->created_at?->format('Y-m-d H:i'),
                'can_delete' => true,
            ],
        ]);
    }

    public function update(Request $request, MaintRequest $maintRequest, MaintRequestImageAnnotation $annotation)
    {
        abort_unless($annotation->maint_request_id === $maintRequest->id, 404);
        abort_unless($this->canModify($annotation), 403);

        $data = $request->validate([
            'color'   => 'nullable|string|max:16',
            'payload' => 'nullable|array',
        ]);
        $annotation->update(array_filter($data, fn ($v) => $v !== null));
        return response()->json(['ok' => true]);
    }

    public function destroy(MaintRequest $maintRequest, MaintRequestImageAnnotation $annotation)
    {
        abort_unless($annotation->maint_request_id === $maintRequest->id, 404);
        abort_unless($this->canModify($annotation), 403);

        $annotation->delete();
        return response()->json(['ok' => true]);
    }

    private function canModify(MaintRequestImageAnnotation $a): bool
    {
        $u = auth()->user();
        return $u && ($u->id === $a->user_id || $u->isAdmin());
    }
}
