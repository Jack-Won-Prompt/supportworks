@extends('layouts.app')

@section('title', __('team.page_title'))

@section('header-actions')@endsection

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- ───── 이메일 초대 카드 ───── --}}
    <div style="background:#fff;border-radius:14px;border:1px solid var(--t100);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <div id="invite-accordion-header" onclick="toggleInviteAccordion()"
            style="padding:20px 24px 16px;border-bottom:1px solid transparent;cursor:pointer;display:flex;align-items:center;justify-content:space-between;user-select:none;transition:border-color .15s;"
            onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--t300),var(--t500));display:flex;align-items:center;justify-content:center;">
                    <svg width="17" height="17" fill="none" stroke="#fff" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size:15px;font-weight:700;color:#1e1b2e;margin:0;line-height:1.2;">{{ __('team.invite_title') }}</h2>
                    <p style="font-size:12px;color:#9ca3af;margin:0;">{{ __('team.invite_desc') }}</p>
                </div>
            </div>
            <svg id="invite-chevron" width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                style="flex-shrink:0;transition:transform .2s;transform:rotate(-90deg);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        <div id="invite-accordion-body" style="display:none;">
        <div style="padding:20px 24px;">
            <form method="POST" action="{{ route('team.invite') }}">
                @csrf
                <input type="hidden" name="invite_lang" id="invite-lang-field" value="">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <div style="flex:1.4;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">{{ __('team.email_label') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="teammate@example.com"
                            style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;color:#1e1b2e;outline:none;transition:border-color .15s;box-sizing:border-box;"
                            onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                        @error('email')
                            <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div style="flex:1;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">
                            {{ __('team.phone_label') }} <span style="font-weight:400;color:#9ca3af;">{{ __('team.phone_hint') }}</span>
                        </label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                            placeholder="010-0000-0000"
                            maxlength="20"
                            style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;color:#1e1b2e;outline:none;transition:border-color .15s;box-sizing:border-box;"
                            onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                        @error('phone')
                            <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div style="padding-top:22px;">
                        <button type="submit" id="invite-btn"
                            style="display:flex;align-items:center;gap:7px;padding:9px 20px;background:linear-gradient(135deg,var(--t500),var(--t600));color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap;transition:opacity .15s;"
                            onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            {{ __('team.send_invite') }}
                        </button>
                    </div>
                </div>

                {{-- 프로젝트 선택 --}}
                @if($projects->isNotEmpty())
                <div style="margin-top:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;">
                        {{ __('team.project_select_label') }} <span style="font-weight:400;color:#9ca3af;">{{ __('team.project_select_hint') }}</span>
                    </label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;" id="project-checkboxes">
                        @foreach($projects as $proj)
                        <label style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;font-size:13px;color:#374151;transition:all .15s;user-select:none;"
                               class="proj-chip"
                               onmouseover="this.style.borderColor='var(--t300)'"
                               onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e5e7eb'">
                            <input type="checkbox" name="project_ids[]" value="{{ $proj->id }}"
                                   style="width:14px;height:14px;accent-color:var(--t500);cursor:pointer;"
                                   onchange="updateChip(this)">
                            {{ $proj->name }}
                            <span style="font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600;
                                {{ $proj->status === 'active' ? 'background:#dcfce7;color:#16a34a' : 'background:#f1f5f9;color:#64748b' }}">
                                {{ $proj->status === 'active' ? __('team.project_active') : $proj->status_label ?? $proj->status }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- 초대 메시지 --}}
                <div style="margin-top:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <label style="font-size:12px;font-weight:600;color:#6b7280;">
                            {{ __('team.invite_message_label') }} <span style="font-weight:400;color:#9ca3af;">{{ __('team.invite_message_hint') }}</span>
                        </label>
                        {{-- 번역 언어 선택기 --}}
                        <div style="position:relative;">
                            <button type="button" id="inv-translate-btn" onclick="toggleInvTranslate(event)"
                                style="display:flex;align-items:center;gap:4px;padding:3px 9px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:11px;font-weight:700;color:#9ca3af;background:#fff;cursor:pointer;font-family:inherit;transition:all .15s;">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                                <span id="inv-translate-label">{{ __('team.invite_translate_label') }}</span>
                            </button>
                            <div id="inv-translate-picker" style="display:none;position:absolute;right:0;top:calc(100%+4px);background:#fff;border:1px solid #e5e7eb;border-radius:9px;box-shadow:0 8px 24px rgba(0,0,0,.1);padding:5px;min-width:120px;z-index:200;">
                                <button type="button" class="inv-tlp-item" data-lang="" data-label="{{ __('team.invite_translate_label') }}" onclick="setInvTranslateLang(this)" style="display:block;width:100%;padding:6px 10px;font-size:12px;font-weight:500;color:#374151;background:none;border:none;border-radius:7px;cursor:pointer;text-align:left;font-family:inherit;transition:background .1s;">✕ {{ __('team.translate_none') }}</button>
                                <button type="button" class="inv-tlp-item" data-lang="en" data-label="English" onclick="setInvTranslateLang(this)" style="display:block;width:100%;padding:6px 10px;font-size:12px;font-weight:500;color:#374151;background:none;border:none;border-radius:7px;cursor:pointer;text-align:left;font-family:inherit;transition:background .1s;">🇺🇸 English</button>
                                <button type="button" class="inv-tlp-item" data-lang="ko" data-label="한국어" onclick="setInvTranslateLang(this)" style="display:block;width:100%;padding:6px 10px;font-size:12px;font-weight:500;color:#374151;background:none;border:none;border-radius:7px;cursor:pointer;text-align:left;font-family:inherit;transition:background .1s;">🇰🇷 한국어</button>
                                <button type="button" class="inv-tlp-item" data-lang="ja" data-label="日本語" onclick="setInvTranslateLang(this)" style="display:block;width:100%;padding:6px 10px;font-size:12px;font-weight:500;color:#374151;background:none;border:none;border-radius:7px;cursor:pointer;text-align:left;font-family:inherit;transition:background .1s;">🇯🇵 日本語</button>
                                <button type="button" class="inv-tlp-item" data-lang="zh" data-label="中文" onclick="setInvTranslateLang(this)" style="display:block;width:100%;padding:6px 10px;font-size:12px;font-weight:500;color:#374151;background:none;border:none;border-radius:7px;cursor:pointer;text-align:left;font-family:inherit;transition:background .1s;">🇨🇳 中文</button>
                            </div>
                        </div>
                    </div>
                    <textarea id="invite-message-textarea" name="message" rows="3" maxlength="500"
                        placeholder="{{ __('team.invite_message_placeholder') }}"
                        style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#1e1b2e;outline:none;resize:vertical;transition:border-color .15s;box-sizing:border-box;font-family:inherit;line-height:1.6;"
                        onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'"
                    >{{ old('message') }}</textarea>
                    <div id="inv-translate-status" style="display:none;font-size:11px;color:var(--t500);margin-top:4px;display:flex;align-items:center;gap:4px;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                        <span id="inv-translate-status-text"></span>
                    </div>
                    @error('message')
                        <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            {{-- 발송 실패 시 링크 복사 fallback --}}
            @if(session('invite_url') && session('mail_error'))
            <div style="margin-top:16px;padding:14px 16px;background:#fef3c7;border:1.5px solid #fde68a;border-radius:10px;">
                <p style="font-size:12px;font-weight:700;color:#92400e;margin:0 0 4px;">{{ __('team.mail_fail_title') }}</p>
                <p style="font-size:11px;color:#b45309;margin:0 0 10px;">{{ session('mail_error') }}</p>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input id="invite-link-input" type="text" readonly value="{{ session('invite_url') }}"
                        style="flex:1;padding:7px 11px;background:#fff;border:1px solid #fde68a;border-radius:7px;font-size:12px;color:#4b5563;outline:none;min-width:0;">
                    <button onclick="copyInviteLink()"
                        style="padding:7px 14px;background:#fff;border:1.5px solid #fcd34d;border-radius:7px;font-size:12px;font-weight:600;color:#92400e;cursor:pointer;white-space:nowrap;transition:background .15s;"
                        onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#fff'">
                        {{ __('team.copy') }}
                    </button>
                </div>
            </div>
            @endif
        </div>
        </div>{{-- /invite-accordion-body --}}
    </div>

    {{-- ───── 대기 중인 초대 ───── --}}
    @if($invitations->count() > 0)
    <div style="background:#fff;border-radius:14px;border:1px solid var(--t100);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <div style="padding:16px 24px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:14px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.pending_invites_title') }}</h3>
            <span style="font-size:12px;color:#9ca3af;">{{ __('team.invite_count', ['count' => $invitations->count()]) }}</span>
        </div>
        <div style="padding:8px 0;">
            @foreach($invitations as $inv)
            @php $invProjectNames = $projects->whereIn('id', $inv->project_ids ?? [])->pluck('name'); @endphp
            <div style="padding:12px 24px;transition:background .12s;border-bottom:1px solid #f9f9f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $inv->email }}</div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:1px;">{{ $inv->inviter?->name ?? __('team.unknown_inviter') }} {{ __('team.invited_by') }} · {{ $inv->created_at->diffForHumans() }}</div>
                    </div>
                    <span style="font-size:11px;padding:2px 8px;background:#fef3c7;color:#92400e;border-radius:5px;font-weight:600;flex-shrink:0;">{{ __('team.pending_badge') }}</span>
                    @if($inv->invited_by === auth()->id() || auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('team.invite.cancel', $inv) }}" onsubmit="return confirm('{{ __('team.cancel_invite_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="padding:4px 10px;background:transparent;border:1px solid #fecaca;border-radius:6px;font-size:11px;color:#ef4444;cursor:pointer;transition:background .12s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">{{ __('team.cancel_invite') }}</button>
                    </form>
                    @endif
                </div>
                {{-- 초대된 프로젝트 --}}
                @if($invProjectNames->isNotEmpty())
                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:5px;padding-left:48px;">
                    <span style="font-size:10.5px;color:#9ca3af;font-weight:600;margin-right:2px;">{{ __('team.invite_projects_label') }}:</span>
                    @foreach($invProjectNames as $pName)
                    <span style="font-size:10.5px;padding:1px 7px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:4px;font-weight:600;">{{ $pName }}</span>
                    @endforeach
                </div>
                @endif
                {{-- 초대 메시지 --}}
                @if($inv->message)
                <div style="margin-top:6px;padding:7px 12px;background:#f8f9fa;border-left:3px solid var(--t300);border-radius:0 6px 6px 0;margin-left:48px;">
                    <p style="font-size:12px;color:#4b5563;margin:0;line-height:1.5;white-space:pre-wrap;">{{ $inv->message }}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ───── 구성원 리스트 ───── --}}
    <div style="background:#fff;border-radius:14px;border:1px solid var(--t100);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <div style="padding:16px 24px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:14px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.member_list_title') }}</h3>
            <span style="font-size:12px;color:#9ca3af;">{{ __('team.member_total') }} {{ $members->count() }}{{ __('team.member_count_unit') }}</span>
        </div>

        {{-- 검색 --}}
        <div style="padding:12px 24px;border-bottom:1px solid #f9f9f9;">
            <input id="member-search" type="text" placeholder="{{ __('team.search_placeholder') }}"
                style="width:100%;padding:8px 13px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#374151;outline:none;transition:border-color .15s;box-sizing:border-box;"
                onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        <div id="member-list" style="padding:8px 0;">
            @forelse($members as $member)
            @php
                $avatarColors = ['#a394f9','#7dd3fc','#6ee7b7','#fcd34d','#f9a8d4','#c4b5fd'];
                $color = $avatarColors[crc32($member->email) % count($avatarColors)];
                $isMe = $member->id === auth()->id();
                $memberProjects = $memberProjectMap[$member->id] ?? collect();
                $memberProjIds  = $memberProjectIdMap[$member->id] ?? collect();
            @endphp
            <div class="member-row"
                data-name="{{ strtolower($member->name) }}"
                data-email="{{ strtolower($member->email) }}"
                data-member-id="{{ $member->id }}"
                data-member-name="{{ $member->name }}"
                data-project-ids="{{ json_encode($memberProjIds->values()) }}"
                style="display:flex;align-items:center;gap:14px;padding:12px 24px;transition:background .12s;"
                onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'">

                {{-- 아바타 --}}
                <div style="width:40px;height:40px;border-radius:50%;background:{{ $color }};display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                    {{ mb_substr($member->name, 0, 1) }}
                </div>

                {{-- 정보 --}}
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:14px;font-weight:600;color:#1e1b2e;">{{ $member->name }}</span>
                        @if($isMe)
                            <span style="font-size:10px;padding:1px 6px;background:var(--t100);color:var(--tText);border-radius:4px;font-weight:600;">{{ __('team.me_badge') }}</span>
                        @endif
                    </div>
                    <div style="font-size:12px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $member->email }}</div>
                    @if($canManage)
                        @php
                            $curCo = $companies->firstWhere('id', $member->company_group_id);
                            $curCoName = $curCo?->name ?? $member->company;
                        @endphp
                        <div style="margin-top:3px;display:flex;align-items:center;gap:6px;">
                            <button type="button" class="member-company-btn" data-member-id="{{ $member->id }}"
                                data-company-id="{{ $member->company_group_id }}"
                                data-company-name="{{ $curCoName }}"
                                data-member-name="{{ $member->name }}"
                                onclick="openCompanyPicker(this)"
                                style="display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:2px 8px;border:1px solid var(--t100);border-radius:999px;background:#fff;color:{{ $curCoName ? 'var(--t700)' : '#9ca3af' }};max-width:200px;cursor:pointer;outline:none;transition:background .12s,border-color .12s;"
                                onmouseover="this.style.background='var(--t50)';this.style.borderColor='var(--t300)'"
                                onmouseout="this.style.background='#fff';this.style.borderColor='var(--t100)'">
                                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0v-4m-9 0h4v4m-4 0v-4m0-4h.01M11 13h.01M15 13h.01M11 9h.01M15 9h.01M7 9h.01M7 13h.01"/></svg>
                                <span class="member-company-label" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:170px;">{{ $curCoName ?: __('team.company_select') }}</span>
                            </button>
                            <span class="member-company-status" style="font-size:10px;color:#15803d;display:none;">{{ __('team.company_saved') }}</span>
                        </div>
                    @elseif($member->company)
                        <div style="font-size:11px;color:var(--t300);margin-top:1px;">{{ $member->company }}</div>
                    @endif
                    @if($member->role !== 'admin')
                    <div class="member-project-badges" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:5px;">
                        @foreach($memberProjects as $pName)
                        <span style="font-size:10px;padding:1px 7px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;font-weight:600;">{{ $pName }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- 역할 배지 --}}
                <div style="flex-shrink:0;text-align:right;">
                    @if($member->role === 'admin')
                        <span style="font-size:11px;padding:3px 9px;background:#fce7f3;color:#be185d;border-radius:6px;font-weight:700;">{{ __('team.role_admin') }}</span>
                    @elseif($member->role === 'member')
                        <span style="font-size:11px;padding:3px 9px;background:#dbeafe;color:#1d4ed8;border-radius:6px;font-weight:700;">{{ __('team.role_member') }}</span>
                    @else
                        <span style="font-size:11px;padding:3px 9px;background:#f3f4f6;color:#6b7280;border-radius:6px;font-weight:700;">{{ __('team.role_external') }}</span>
                    @endif
                    <div style="font-size:11px;color:#d1d5db;margin-top:3px;">{{ $member->created_at->format('Y.m.d') }} {{ __('team.joined_date_suffix') }}</div>
                </div>

                {{-- 프로젝트 배정 버튼 (관리자/매니저만) --}}
                @if($canManage)
                <button type="button"
                    onclick="openProjectModal(this.closest('.member-row'))"
                    style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--t100);color:var(--t500);background:transparent;cursor:pointer;transition:background .12s,color .12s;"
                    title="{{ __('team.assign_projects') }}"
                    onmouseover="this.style.background='var(--t100)';this.style.color='var(--tText)'" onmouseout="this.style.background='transparent';this.style.color='var(--t500)'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </button>
                @endif

                {{-- 메시지 보내기 --}}
                @if(!$isMe)
                <a href="{{ route('messages.index') }}?to={{ $member->id }}"
                    style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--t100);color:var(--t500);text-decoration:none;transition:background .12s,color .12s;"
                    title="{{ __('team.send_message') }} {{ $member->name }}"
                    onmouseover="this.style.background='var(--t100)';this.style.color='var(--tText)'" onmouseout="this.style.background='transparent';this.style.color='var(--t500)'">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </a>
                @endif
            </div>
            @empty
            <div style="padding:40px 24px;text-align:center;color:#9ca3af;font-size:14px;">{{ __('team.no_members') }}</div>
            @endforelse
        </div>
    </div>

</div>

@if($canManage)
{{-- ─── 프로젝트 배정 모달 ─── --}}
<div id="proj-modal-backdrop"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9990;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:440px;max-width:calc(100vw - 32px);max-height:80vh;display:flex;flex-direction:column;overflow:hidden;">

        {{-- 헤더 --}}
        <div style="padding:18px 20px 14px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div>
                <div style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('team.assign_projects') }}</div>
                <div id="proj-modal-subtitle" style="font-size:12px;color:#9ca3af;margin-top:1px;"></div>
            </div>
            <button type="button" onclick="closeProjectModal()"
                style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#9ca3af;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:background .12s;"
                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- 검색 --}}
        <div style="padding:12px 20px 8px;flex-shrink:0;">
            <input id="proj-modal-search" type="text" placeholder="{{ __('team.proj_search_placeholder') }}"
                oninput="filterModalProjects(this.value)"
                style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;box-sizing:border-box;transition:border-color .15s;"
                onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        {{-- 프로젝트 목록 --}}
        <div id="proj-modal-list" style="overflow-y:auto;flex:1;padding:4px 20px 12px;">
            {{-- JS로 렌더링 --}}
        </div>

        {{-- 푸터 --}}
        <div style="padding:12px 20px;border-top:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fafafa;">
            <span id="proj-modal-count" style="font-size:12px;color:#9ca3af;"></span>
            <div style="display:flex;gap:8px;">
                <button type="button" onclick="closeProjectModal()"
                    style="padding:8px 16px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;font-size:13px;color:#6b7280;cursor:pointer;font-weight:600;transition:background .12s;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('team.cancel_invite') }}</button>
                <button type="button" id="proj-modal-save" onclick="saveProjectAssignment()"
                    style="padding:8px 20px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--t500),var(--t600));color:#fff;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">{{ __('team.save_btn') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ─── 회사 선택/추가 다이얼로그 (고정 사이즈) ─── --}}
<div id="company-modal-backdrop"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9995;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:460px;height:560px;max-width:calc(100vw - 32px);max-height:calc(100vh - 32px);display:flex;flex-direction:column;overflow:hidden;">
        <div style="padding:18px 20px 12px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div>
                <div style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('team.company_picker_title') }}</div>
                <div id="company-modal-subtitle" style="font-size:12px;color:#9ca3af;margin-top:1px;"></div>
            </div>
            <button type="button" onclick="closeCompanyPicker()"
                style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#9ca3af;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:background .12s;"
                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div style="padding:12px 20px 8px;flex-shrink:0;">
            <input id="company-modal-search" type="text" placeholder="{{ __('team.company_search_placeholder') }}"
                oninput="onCompanySearchInput()" onkeydown="onCompanySearchKey(event)"
                autocomplete="off"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;box-sizing:border-box;transition:border-color .15s;"
                onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        <div id="company-modal-list" style="overflow-y:auto;flex:1 1 auto;padding:4px 12px 8px;">
            {{-- JS 로 렌더링 --}}
        </div>

        <div id="company-modal-empty" style="display:none;padding:14px 20px;border-top:1px solid #f3f4f6;background:#fefce8;flex-shrink:0;">
            <div style="font-size:12px;color:#92400e;margin-bottom:8px;">
                "<span id="company-modal-newname" style="font-weight:700;"></span>" {{ __('team.company_not_registered_suffix') }}
            </div>
            <button type="button" onclick="addNewCompany()"
                style="width:100%;padding:9px 14px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--t500),var(--t600));color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:opacity .12s;"
                onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('team.company_add_select') }}</span>
            </button>
        </div>

        <div style="padding:11px 20px;border-top:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fafafa;gap:8px;">
            <button type="button" onclick="clearCompany()"
                style="padding:7px 12px;border:1.5px solid #fecaca;border-radius:8px;background:#fff;font-size:12px;color:#dc2626;cursor:pointer;font-weight:600;transition:background .12s;"
                onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">{{ __('team.company_clear') }}</button>
            <button type="button" onclick="closeCompanyPicker()"
                style="padding:7px 14px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;font-size:12.5px;color:#6b7280;cursor:pointer;font-weight:600;transition:background .12s;"
                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('common.close') }}</button>
        </div>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush

@section('scripts')
<script>
// ── 이메일 초대 아코디언 ──────────────────────────────────────
async function toggleInviteAccordion() {
    const body    = document.getElementById('invite-accordion-body');
    const chevron = document.getElementById('invite-chevron');
    const header  = document.getElementById('invite-accordion-header');
    const open    = body.style.display !== 'none';
    body.style.display         = open ? 'none' : 'block';
    chevron.style.transform    = open ? 'rotate(-90deg)' : 'rotate(0deg)';
    header.style.borderBottomColor = open ? 'transparent' : 'var(--tBg)';
}

@if($errors->has('email') || session('invite_url') || session('mail_error') || old('email'))
(async function() {
    const body    = document.getElementById('invite-accordion-body');
    const chevron = document.getElementById('invite-chevron');
    const header  = document.getElementById('invite-accordion-header');
    if (body) {
        body.style.display = 'block';
        chevron.style.transform = 'rotate(0deg)';
        header.style.borderBottomColor = 'var(--tBg)';
    }
})();
@endif

// 프로젝트 칩 선택 스타일
async function updateChip(checkbox) {
    const label = checkbox.closest('label');
    if (checkbox.checked) {
        label.style.borderColor = 'var(--t400)';
        label.style.background  = 'var(--tBg)';
        label.style.color       = 'var(--t700)';
    } else {
        label.style.borderColor = '#e5e7eb';
        label.style.background  = '';
        label.style.color       = '#374151';
    }
}

// ── 초대 메시지 번역 ──────────────────────────────────────
let invTranslateLang  = '';
let invTranslateLabel = '';

async function toggleInvTranslate(e) {
    e.stopPropagation();
    const p = document.getElementById('inv-translate-picker');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
}
async function setInvTranslateLang(btn) {
    invTranslateLang  = btn.dataset.lang;
    invTranslateLabel = btn.dataset.label;
    document.getElementById('invite-lang-field').value = invTranslateLang;
    document.getElementById('inv-translate-label').textContent = invTranslateLabel || '{{ __('team.invite_translate_label') }}';
    const mainBtn = document.getElementById('inv-translate-btn');
    mainBtn.style.borderColor = invTranslateLang ? 'var(--t400)' : '#e5e7eb';
    mainBtn.style.color       = invTranslateLang ? 'var(--t500)' : '#9ca3af';
    mainBtn.style.background  = invTranslateLang ? 'var(--t50)'  : '#fff';
    document.querySelectorAll('.inv-tlp-item').forEach(b => b.style.background = b === btn ? 'var(--tBg)' : '');
    document.getElementById('inv-translate-picker').style.display = 'none';

    const statusEl = document.getElementById('inv-translate-status');
    const statusText = document.getElementById('inv-translate-status-text');
    if (invTranslateLang) {
        statusEl.style.display = 'flex';
        statusText.textContent = '{{ __('team.translate_on_submit') }}'.replace(':lang', invTranslateLabel);
    } else {
        statusEl.style.display = 'none';
    }
}
document.addEventListener('click', () => {
    const p = document.getElementById('inv-translate-picker');
    if (p) p.style.display = 'none';
});

// 초대 폼 로딩 상태 + 번역
const inviteForm = document.querySelector('form[action="{{ route('team.invite') }}"]');
if (inviteForm) {
    inviteForm.addEventListener('submit', async function(e) {
        const textarea = document.getElementById('invite-message-textarea');
        const body     = textarea ? textarea.value.trim() : '';

        if (invTranslateLang && body) {
            e.preventDefault();
            const btn = document.getElementById('invite-btn');
            if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; btn.textContent = '{{ __("team.translating") }}'; }

            try {
                const tResp = await fetch('{{ route('translate') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ text: body, target: invTranslateLang }),
                });
                const tData = await tResp.json();
                if (tData.ok && textarea) {
                    textarea.value = body + '\n\n[' + invTranslateLabel + ']\n' + tData.translated;
                }
            } catch(err) { /* 실패 시 원문 전송 */ }

            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> {{ __("team.send_invite") }}';
            }
            inviteForm.submit();
            return;
        }

        // 번역 없음 — 기존 로딩 처리
        const btn = document.getElementById('invite-btn');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> {{ __("team.sending") }}';
        }
    });
}

// 초대 링크 복사
async function copyInviteLink() {
    const input = document.getElementById('invite-link-input');
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(input.value).then(() => {
            showCopyToast();
        }).catch(() => {
            document.execCommand('copy');
            showCopyToast();
        });
    } catch(e) {
        document.execCommand('copy');
        showCopyToast();
    }
}

async function showCopyToast() {
    if (typeof window.showToast === 'function') {
        window.showToast('{{ __("team.clipboard_copied") }}', '{{ __("team.clipboard_copied_msg") }}', null);
    }
}

// 팀원 실시간 검색
const searchInput = document.getElementById('member-search');
if (searchInput) {
    searchInput.addEventListener('input', async function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.member-row').forEach(async function(row) {
            const name  = row.dataset.name  || '';
            const email = row.dataset.email || '';
            row.style.display = (!q || name.includes(q) || email.includes(q)) ? '' : 'none';
        });
    });
}

@if($canManage)
// ── 프로젝트 배정 모달 ──────────────────────────────────────────────────────
const ASSIGNABLE_PROJECTS = @json($assignableProjects->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'status' => $p->status]));
const PROJ_ASSIGN_URL     = '{{ rtrim(url('team/members'), '/') }}';
const CSRF_TOKEN          = document.querySelector('meta[name="csrf-token"]')?.content || '';

let _modalRow    = null; // 현재 열린 행
let _checkedIds  = new Set();

async function openProjectModal(row) {
    _modalRow   = row;
    _checkedIds = new Set(JSON.parse(row.dataset.projectIds || '[]').map(Number));

    document.getElementById('proj-modal-subtitle').textContent =
        @json(__('team.proj_modal_subtitle')).replace(':name', row.dataset.memberName);
    document.getElementById('proj-modal-search').value = '';

    renderModalProjects(ASSIGNABLE_PROJECTS);
    updateModalCount();

    const backdrop = document.getElementById('proj-modal-backdrop');
    backdrop.style.display = 'flex';
    document.getElementById('proj-modal-search').focus();
}

async function closeProjectModal() {
    document.getElementById('proj-modal-backdrop').style.display = 'none';
    _modalRow = null;
}

// 배경 클릭으로 닫기
document.getElementById('proj-modal-backdrop')?.addEventListener('click', async function(e) {
    if (e.target === this) closeProjectModal();
});

async function renderModalProjects(list) {
    const container = document.getElementById('proj-modal-list');
    if (!list.length) {
        container.innerHTML = '<div style="padding:24px 0;text-align:center;color:#9ca3af;font-size:13px;">{{ __('team.proj_none_assignable') }}</div>';
        return;
    }
    container.innerHTML = list.map(p => {
        const checked  = _checkedIds.has(p.id);
        const isActive = p.status === 'active';
        return `
        <label style="display:flex;align-items:center;gap:10px;padding:9px 4px;cursor:pointer;border-radius:8px;transition:background .1s;"
               class="proj-modal-item" data-project-id="${p.id}" data-project-name="${escHtml(p.name)}"
               onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
            <input type="checkbox" data-id="${p.id}" ${checked ? 'checked' : ''}
                onchange="toggleProjectCheck(${p.id}, this.checked)"
                style="width:16px;height:16px;accent-color:var(--t500);cursor:pointer;flex-shrink:0;">
            <span style="flex:1;font-size:13px;font-weight:500;color:#1e1b2e;">${escHtml(p.name)}</span>
            <span style="font-size:10px;padding:1px 7px;border-radius:10px;font-weight:600;flex-shrink:0;
                ${isActive ? 'background:#dcfce7;color:#16a34a;' : 'background:#f1f5f9;color:#64748b;'}">
                ${isActive ? '{{ __('team.proj_in_progress') }}' : p.status}
            </span>
        </label>`;
    }).join('');
}

async function filterModalProjects(q) {
    q = q.toLowerCase().trim();
    const filtered = q
        ? ASSIGNABLE_PROJECTS.filter(p => p.name.toLowerCase().includes(q))
        : ASSIGNABLE_PROJECTS;
    renderModalProjects(filtered);
}

async function toggleProjectCheck(id, checked) {
    if (checked) _checkedIds.add(id);
    else         _checkedIds.delete(id);
    updateModalCount();
}

async function updateModalCount() {
    document.getElementById('proj-modal-count').textContent =
        @json(__('team.proj_count_selected')).replace(':count', _checkedIds.size);
}

async function saveProjectAssignment() {
    if (!_modalRow) return;

    const memberId  = _modalRow.dataset.memberId;
    const saveBtn   = document.getElementById('proj-modal-save');
    saveBtn.disabled = true;
    saveBtn.textContent = '{{ __('team.saving') }}';

    try {
        const resp = await fetch(`${PROJ_ASSIGN_URL}/${memberId}/projects`, {
            method:  'PATCH',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     CSRF_TOKEN,
                'Accept':           'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ project_ids: [..._checkedIds] }),
        });

        const data = await resp.json();
        if (!data.ok) throw new Error('{{ __('team.server_error') }}');

        // 행의 프로젝트 배지 갱신
        _modalRow.dataset.projectIds = JSON.stringify(data.projects.map(p => p.id));
        const badgeContainer = _modalRow.querySelector('.member-project-badges');
        if (badgeContainer) {
            badgeContainer.innerHTML = data.projects.map(p =>
                `<span style="font-size:10px;padding:1px 7px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;font-weight:600;">${escHtml(p.name)}</span>`
            ).join('');
        }

        closeProjectModal();
        if (typeof window.showToast === 'function') {
            window.showToast('{{ __('team.proj_save_done_title') }}', '{{ __('team.proj_save_done_msg') }}', null);
        }
    } catch (err) {
        alert('{{ __('team.proj_save_fail') }}');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = '{{ __('team.save_btn') }}';
    }
}

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── 회사 선택/추가 — 커스텀 다이얼로그 ──────────────────────────────────────
let _coTargetBtn   = null;          // 현재 선택 대상 멤버 버튼
let _coSearchTimer = null;
let _coLastResults = [];

function openCompanyPicker(btn) {
    _coTargetBtn = btn;
    const memberName = btn.dataset.memberName || '';
    const curName    = btn.dataset.companyName || '';
    document.getElementById('company-modal-subtitle').textContent =
        @json(__('team.company_picker_subtitle')).replace(':name', memberName);
    const input = document.getElementById('company-modal-search');
    input.value = curName || '';
    document.getElementById('company-modal-backdrop').style.display = 'flex';
    setTimeout(() => { input.focus(); input.select(); }, 30);
    fetchCompanySuggestions(curName || '');
}

function closeCompanyPicker() {
    document.getElementById('company-modal-backdrop').style.display = 'none';
    _coTargetBtn = null;
}

function onCompanySearchInput() {
    clearTimeout(_coSearchTimer);
    _coSearchTimer = setTimeout(() => {
        fetchCompanySuggestions(document.getElementById('company-modal-search').value.trim());
    }, 180);
}

function onCompanySearchKey(e) {
    if (e.key === 'Escape') { e.preventDefault(); closeCompanyPicker(); return; }
    if (e.key === 'Enter') {
        e.preventDefault();
        const matched = (_coLastResults || []).find(r =>
            r.name.trim().toLowerCase() === e.target.value.trim().toLowerCase()
        );
        if (matched) {
            selectCompany(matched.id, matched.name, !!matched.from_group);
        } else {
            // 정확 일치 없음 → 새 회사 추가
            addNewCompany();
        }
    }
}

async function fetchCompanySuggestions(q) {
    const list = document.getElementById('company-modal-list');
    const empty = document.getElementById('company-modal-empty');
    list.innerHTML = '<div style="padding:18px;text-align:center;color:#9ca3af;font-size:12px;">{{ __('team.company_searching') }}</div>';
    empty.style.display = 'none';
    try {
        const r = await fetch('{{ route('team.companies.search') }}?q=' + encodeURIComponent(q), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
        });
        const d = await r.json();
        _coLastResults = d.companies || [];
        renderCompanyList(_coLastResults, q);
    } catch (e) {
        list.innerHTML = '<div style="padding:18px;text-align:center;color:#dc2626;font-size:12px;">{{ __('team.company_load_fail') }}</div>';
    }
}

function renderCompanyList(items, q) {
    const list  = document.getElementById('company-modal-list');
    const empty = document.getElementById('company-modal-empty');
    const trimmed = (q || '').trim();

    if (!items.length) {
        list.innerHTML = '<div style="padding:18px;text-align:center;color:#9ca3af;font-size:12px;">' + (trimmed ? '{{ __('team.company_no_match') }}' : '{{ __('team.company_none_registered') }}') + '</div>';
    } else {
        const cur = _coTargetBtn ? (_coTargetBtn.dataset.companyId || '') : '';
        list.innerHTML = items.map(c => {
            const isCur = c.id && String(c.id) === String(cur);
            const highlighted = trimmed
                ? escHtml(c.name).replace(new RegExp(escapeRegex(trimmed), 'gi'), m => `<mark style="background:var(--t100);color:var(--t700);padding:0 1px;border-radius:2px;">${m}</mark>`)
                : escHtml(c.name);
            return `<div class="co-suggest-row" onclick='selectCompany(${c.id}, ${JSON.stringify(c.name)})' style="display:flex;align-items:center;gap:6px;padding:9px 12px;border-radius:8px;cursor:pointer;${isCur ? 'background:var(--t50);' : ''}transition:background .1s;" onmouseover="this.style.background='var(--t50)'" onmouseout="this.style.background='${isCur ? 'var(--t50)' : 'transparent'}'">
                <span style="flex:1;font-size:13px;color:#1f2937;${isCur ? 'font-weight:700;color:var(--t700);' : ''}">${highlighted}</span>
                ${isCur ? '<svg width="14" height="14" fill="none" stroke="var(--t600)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>' : ''}
            </div>`;
        }).join('');
    }

    // 정확 일치 없을 때 새 회사 추가 안내
    const exactExists = trimmed && items.some(c => c.name.trim().toLowerCase() === trimmed.toLowerCase());
    if (trimmed && !exactExists) {
        document.getElementById('company-modal-newname').textContent = trimmed;
        empty.style.display = 'block';
    } else {
        empty.style.display = 'none';
    }
}

async function selectCompany(companyId, name) {
    await assignCompany(companyId, name);
}

async function addNewCompany() {
    const name = (document.getElementById('company-modal-search').value || '').trim();
    if (!name) return;
    try {
        const r = await fetch('{{ route('team.companies.store') }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '{{ __('team.company_register_fail') }}');
        await assignCompany(d.company.id, d.company.name);
    } catch (e) {
        alert('{{ __('team.company_register_fail_msg') }}' + e.message);
    }
}

async function clearCompany() {
    await assignCompany(null, null);
}

async function assignCompany(companyId, name) {
    if (!_coTargetBtn) return;
    const memberId = _coTargetBtn.dataset.memberId;
    const status = _coTargetBtn.parentElement.querySelector('.member-company-status');
    const url = '{{ url('team/members') }}/' + memberId + '/company';
    try {
        const r = await fetch(url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ company_id: companyId || null }),
        });
        if (!r.ok) {
            const txt = await r.text();
            throw new Error('{{ __('team.company_server_response') }}' + r.status + (txt.length < 200 ? ': ' + txt : ''));
        }
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '{{ __('team.proj_save_fail') }}');

        const label = _coTargetBtn.querySelector('.member-company-label');
        _coTargetBtn.dataset.companyId   = companyId || '';
        _coTargetBtn.dataset.companyName = name || '';
        if (label) label.textContent = name || '{{ __('team.company_select') }}';
        _coTargetBtn.style.color = name ? 'var(--t700)' : '#9ca3af';

        if (status) {
            status.style.display = 'inline';
            status.style.color = '#15803d';
            status.textContent = '{{ __('team.company_saved') }}';
            clearTimeout(status._t);
            status._t = setTimeout(() => { status.style.display = 'none'; }, 1500);
        }
        closeCompanyPicker();
    } catch (e) {
        if (status) {
            status.style.display = 'inline';
            status.style.color = '#dc2626';
            status.textContent = '{{ __('team.company_save_failed') }}';
        }
        alert('{{ __('team.company_assign_fail') }}' + e.message);
    }
}

function escapeRegex(s) { return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

// 배경 클릭 닫기
document.getElementById('company-modal-backdrop')?.addEventListener('click', function(e) {
    if (e.target === this) closeCompanyPicker();
});
@endif
</script>
@endsection
