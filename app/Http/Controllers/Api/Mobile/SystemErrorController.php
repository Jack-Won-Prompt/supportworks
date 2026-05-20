<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemErrorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status', 'unresolved');
        $level  = $request->query('level');
        $search = $request->query('search');

        $query = SystemErrorLog::query()->latest();

        if ($status === 'unresolved') {
            $query->unresolved();
        } elseif ($status === 'resolved') {
            $query->where('is_resolved', true);
        }

        if ($level === 'error') {
            $query->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
        } elseif ($level) {
            $query->where('level', $level);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('exception', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(30);

        return response()->json([
            'data' => collect($paginator->items())->map(fn($e) => $this->resource($e)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
            'stats' => [
                'total'      => SystemErrorLog::count(),
                'unresolved' => SystemErrorLog::unresolved()->count(),
                'resolved'   => SystemErrorLog::where('is_resolved', true)->count(),
                'error'      => SystemErrorLog::whereIn('level', ['error', 'critical', 'alert', 'emergency'])->count(),
                'warning'    => SystemErrorLog::where('level', 'warning')->count(),
                'info'       => SystemErrorLog::where('level', 'info')->count(),
            ],
        ]);
    }

    public function show(Request $request, SystemErrorLog $systemError): JsonResponse
    {
        $this->authorizeAdmin($request);
        return response()->json($this->resource($systemError, full: true));
    }

    public function resolve(Request $request, SystemErrorLog $systemError): JsonResponse
    {
        $this->authorizeAdmin($request);
        $systemError->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
        return response()->json($this->resource($systemError));
    }

    public function resolveAll(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        SystemErrorLog::unresolved()->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403, 'Admin only');
    }

    private function resource(SystemErrorLog $e, bool $full = false): array
    {
        return [
            'id'          => $e->id,
            'level'       => $e->level,
            'exception'   => $e->exception,
            'message'     => $e->message,
            'file'        => $e->file,
            'line'        => $e->line,
            'is_resolved' => (bool) $e->is_resolved,
            'resolved_at' => optional($e->resolved_at)->toIso8601String(),
            'created_at'  => optional($e->created_at)->toIso8601String(),
            'context'     => $full ? ($e->context ?? []) : null,
            'trace'       => $full ? $e->trace : null,
        ];
    }
}