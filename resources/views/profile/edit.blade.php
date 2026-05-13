@extends('layouts.app')

@section('title', __('team.profile_title'))

@section('content')
@php
$inp = 'width:100%;padding:9px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;font-family:inherit;transition:border-color .15s;box-sizing:border-box;';
$lbl = 'display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;';
$card = 'background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:24px;margin-bottom:16px;';
$avatarColors = ['#7c3aed','#2563eb','#059669','#dc2626','#d97706','#db2777','#0891b2','#7c3aed'];
$avatarBg = $avatarColors[$user->id % count($avatarColors)];
$roleStyle = match($user->role) {
    'admin'  => 'background:#fee2e2;color:#dc2626',
    'member' => 'background:#dbeafe;color:#2563eb',
    default  => 'background:#dcfce7;color:#16a34a',
};
$statusDot = match($user->agent_status ?? 'offline') {
    'online' => '#22c55e',
    'away'   => '#f59e0b',
    default  => '#94a3b8',
};
@endphp

<div style="max-width:900px;margin:0 auto;padding:20px 0;">

    {{-- 성공 메시지 --}}
    @if(session('status') === 'profile-updated')
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:11px 16px;margin-bottom:16px;font-size:13px;color:#16a34a;display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ __('team.profile_saved') }}
    </div>
    @endif
    @if(session('status') === 'password-updated')
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:11px 16px;margin-bottom:16px;font-size:13px;color:#16a34a;display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ __('team.password_changed') }}
    </div>
    @endif

    <div style="display:grid;grid-template-columns:260px 1fr;gap:20px;align-items:start;">

        {{-- ── 좌측 프로필 카드 ── --}}
        <div style="position:sticky;top:24px;">

            {{-- 아바타 + 기본 정보 --}}
            <div style="{{ $card }}text-align:center;">
                <div style="position:relative;display:inline-block;margin-bottom:14px;">
                    <div style="width:80px;height:80px;border-radius:50%;background:{{ $avatarBg }};display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;color:#fff;margin:0 auto;">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                    <div style="position:absolute;bottom:2px;right:2px;width:14px;height:14px;border-radius:50%;background:{{ $statusDot }};border:2px solid #fff;"></div>
                </div>

                <div style="font-size:16px;font-weight:800;color:#1e1b2e;margin-bottom:4px;">{{ $user->name }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:10px;">{{ $user->email }}</div>

                <span style="font-size:11px;padding:3px 10px;border-radius:20px;font-weight:700;{{ $roleStyle }}">
                    {{ $user->role_label }}
                </span>

                @if($user->companyGroup)
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f0eeff;font-size:12px;color:#64748b;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    {{ $user->companyGroup->name }}
                </div>
                @endif

                @if($user->phone)
                <div style="margin-top:6px;font-size:12px;color:#64748b;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $user->phone }}
                </div>
                @endif

                <div style="margin-top:8px;font-size:11px;color:#94a3b8;">
                    {{ __('team.joined_date') }} {{ $user->created_at->format('Y.m.d') }}
                </div>
            </div>

            {{-- 활동 통계 --}}
            <div style="{{ $card }}">
                <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px;">{{ __('team.activity_stats') }}</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div style="background:#f5f3ff;border-radius:10px;padding:12px;text-align:center;">
                        <div style="font-size:20px;font-weight:800;color:var(--t600);">{{ $stats['projects'] }}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;font-weight:600;">{{ __('team.stat_projects') }}</div>
                    </div>
                    <div style="background:#eff6ff;border-radius:10px;padding:12px;text-align:center;">
                        <div style="font-size:20px;font-weight:800;color:#2563eb;">{{ $stats['tasks'] }}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;font-weight:600;">Tasks</div>
                    </div>
                    <div style="background:#fdf4ff;border-radius:10px;padding:12px;text-align:center;">
                        <div style="font-size:20px;font-weight:800;color:#9333ea;">{{ $stats['ai_sessions'] }}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;font-weight:600;">{{ __('team.stat_ai_sessions') }}</div>
                    </div>
                    <div style="background:#fdf2f8;border-radius:10px;padding:12px;text-align:center;">
                        <div style="font-size:20px;font-weight:800;color:#db2777;">{{ $stats['minutes'] }}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;font-weight:600;">{{ __('team.stat_minutes') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 우측 폼 영역 ── --}}
        <div>

            {{-- 기본 정보 수정 --}}
            <div style="{{ $card }}">
                <div style="font-size:14px;font-weight:700;color:#1e1b2e;margin-bottom:4px;">{{ __('team.basic_info_title') }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:20px;">{{ __('team.basic_info_desc') }}</div>

                @if($errors->any() && !$errors->hasBag('updatePassword') && !$errors->hasBag('userDeletion'))
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
                    <ul style="margin:0;padding:0 0 0 16px;font-size:12px;color:#dc2626;">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PATCH')

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                        <div>
                            <label style="{{ $lbl }}">{{ __('team.name_label') }} <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   style="{{ $inp }}" placeholder="{{ __('team.person_name_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                        <div>
                            <label style="{{ $lbl }}">{{ __('team.email_label_profile') }} <span style="color:#ef4444;">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   style="{{ $inp }}" placeholder="example@company.com"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
                        <div>
                            <label style="{{ $lbl }}">{{ __('team.company_label') }}</label>
                            <input type="text" name="company" value="{{ old('company', $user->company) }}"
                                   style="{{ $inp }}" placeholder="{{ __('team.company_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                        <div>
                            <label style="{{ $lbl }}">{{ __('team.phone_label') }}</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                   style="{{ $inp }}" placeholder="010-0000-0000"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                    </div>

                    {{-- 읽기 전용 정보 --}}
                    <div style="background:#f8f7ff;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
                        <div style="display:flex;gap:24px;flex-wrap:wrap;">
                            <div>
                                <div style="font-size:11px;font-weight:600;color:#94a3b8;margin-bottom:2px;">{{ __('team.role_label') }}</div>
                                <div style="font-size:13px;font-weight:600;color:#1e1b2e;">{{ $user->role_label }}</div>
                            </div>
                            @if($user->companyGroup)
                            <div>
                                <div style="font-size:11px;font-weight:600;color:#94a3b8;margin-bottom:2px;">{{ __('team.group_label') }}</div>
                                <div style="font-size:13px;font-weight:600;color:#1e1b2e;">{{ $user->companyGroup->name }}</div>
                            </div>
                            @endif
                            <div>
                                <div style="font-size:11px;font-weight:600;color:#94a3b8;margin-bottom:2px;">{{ __('team.joined_date') }}</div>
                                <div style="font-size:13px;font-weight:600;color:#1e1b2e;">{{ $user->created_at->format('Y.m.d') }}</div>
                            </div>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:8px;">{{ __('team.role_admin_only') }}</div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;">
                        <button type="submit"
                                style="padding:9px 24px;background:var(--t600);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;">
                            {{ __('team.save_btn') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- 비밀번호 변경 --}}
            <div style="{{ $card }}">
                <div style="font-size:14px;font-weight:700;color:#1e1b2e;margin-bottom:4px;">{{ __('team.password_title') }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:20px;">{{ __('team.password_desc') }}</div>

                @if($errors->hasBag('updatePassword'))
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
                    <ul style="margin:0;padding:0 0 0 16px;font-size:12px;color:#dc2626;">
                        @foreach($errors->updatePassword->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf @method('PUT')

                    <div style="margin-bottom:14px;">
                        <label style="{{ $lbl }}">{{ __('team.current_password') }}</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               style="{{ $inp }}" placeholder="{{ __('team.current_password_ph') }}"
                               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
                        <div>
                            <label style="{{ $lbl }}">{{ __('team.new_password') }}</label>
                            <input type="password" name="password" autocomplete="new-password"
                                   style="{{ $inp }}" placeholder="{{ __('team.new_password_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                        <div>
                            <label style="{{ $lbl }}">{{ __('team.confirm_password') }}</label>
                            <input type="password" name="password_confirmation" autocomplete="new-password"
                                   style="{{ $inp }}" placeholder="{{ __('team.confirm_password_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;">
                        <button type="submit"
                                style="padding:9px 24px;background:#1e1b2e;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;">
                            {{ __('team.change_password_btn') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection
