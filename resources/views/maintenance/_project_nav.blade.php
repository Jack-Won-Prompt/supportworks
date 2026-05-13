<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="{{ route('projects.show', $project) }}"
       style="padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;
              {{ $active === 'overview' ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">
        {{ __('maintenance.nav_overview') }}
    </a>
    <a href="{{ route('projects.schedules.index', $project) }}"
       style="padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;
              {{ $active === 'schedules' ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">
        {{ __('maintenance.nav_schedule') }}
    </a>
    <a href="{{ route('projects.questions.index', $project) }}"
       style="padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;
              {{ $active === 'questions' ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">
        {{ __('maintenance.nav_qa') }}
    </a>
    <a href="{{ route('projects.files.index', $project) }}"
       style="padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;
              {{ $active === 'files' ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">
        {{ __('maintenance.nav_files') }}
    </a>
    <a href="{{ route('projects.maintenances.index', $project) }}"
       style="padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;
              {{ $active === 'maintenance' ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">
        {{ __('maintenance.nav_sr_receipt') }}
    </a>
</div>
