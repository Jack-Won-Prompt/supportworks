<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\Project;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    public function index(Request $request)
    {
        $admin    = auth('admin')->user();
        $groups   = $this->accessibleGroups($admin);
        $groupIds = $groups->pluck('id');

        $query = Project::with(['companyGroup', 'creator', 'projectMembers'])
            ->when($request->search, fn($q) =>
                $q->where(fn($inner) =>
                    $inner->where('name', 'like', '%'.$request->search.'%')
                          ->orWhere('client_name', 'like', '%'.$request->search.'%')
                )
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->group_id, fn($q) => $q->where('company_group_id', $request->group_id))
            ->when($request->si_mode, fn($q) => $q->where('si_mode_enabled', true))
            ->when(!$admin->isSuperAdmin(),
                fn($q) => $q->whereIn('company_group_id', $groupIds)
            );

        $stats = [
            'total'       => (clone $query)->count(),
            'active'      => (clone $query)->where('status', 'active')->count(),
            'on_hold'     => (clone $query)->where('status', 'on_hold')->count(),
            'completed'   => (clone $query)->where('status', 'completed')->count(),
            'cancelled'   => (clone $query)->where('status', 'cancelled')->count(),
        ];

        $projects = $query->latest()->paginate(20)->withQueryString();

        return view('admin.projects.index', compact('projects', 'groups', 'stats'));
    }

    private function accessibleGroups($admin)
    {
        return $admin->isSuperAdmin()
            ? CompanyGroup::orderBy('name')->get()
            : $admin->companyGroups()->orderBy('name')->get();
    }
}
