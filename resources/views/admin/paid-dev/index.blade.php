@extends('layouts.admin')
@section('title', '유상개발 명세')

@section('content')

{{-- 필터 --}}
<form method="GET" action="{{ route('admin.paid-dev.index') }}" id="filter-form">
<div class="filter-bar" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <select name="company_group_id" onchange="this.form.submit()"
            style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;min-width:180px;">
        @foreach($companies as $c)
            <option value="{{ $c->id }}" {{ $companyId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
        @if($companies->isEmpty())
            <option value="0">(유상개발 내역이 있는 회사 없음)</option>
        @endif
    </select>

    <select name="year" onchange="this.form.submit()"
            style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;min-width:120px;">
        @foreach($years as $y)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}년</option>
        @endforeach
        @if(empty($years))
            <option value="{{ $year }}">{{ $year }}년</option>
        @endif
    </select>
</div>
</form>

{{-- 통계 카드 --}}
<div class="admin-stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#6366f1;">{{ number_format($totalCount) }}</div>
        <div class="admin-stat-lbl">전체 추가개발 건수</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#f59e0b;">{{ number_format($paidCount) }}</div>
        <div class="admin-stat-lbl">유상 건수 (Pay)</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#0891b2;">{{ number_format($paidDays) }}일</div>
        <div class="admin-stat-lbl">유상 총 일수</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#dc2626;font-size:18px;">₩{{ number_format($paidCost) }}</div>
        <div class="admin-stat-lbl">유상 총 금액</div>
    </div>
</div>

{{-- 목록 --}}
<div class="admin-card" style="padding:0;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:14px;font-weight:700;color:#1e293b;margin:0;">{{ $year }}년 유상개발 명세</h3>
        <span style="font-size:12px;color:#94a3b8;">{{ number_format($totalCount) }}건</span>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:80px;">SR번호</th>
                <th style="width:90px;">요청일</th>
                <th>요약</th>
                <th style="width:80px;">담당자</th>
                <th style="width:80px;text-align:center;">구분</th>
                <th style="width:70px;text-align:right;">일수</th>
                <th style="width:120px;text-align:right;">금액</th>
                <th style="width:100px;text-align:center;">상태</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $r)
            @php
                $statusLbl = match($r->status) {
                    'completed'      => ['완료',     '#16a34a', '#dcfce7'],
                    'in_progress'    => ['진행중',   '#0891b2', '#cffafe'],
                    'reviewing'      => ['검토',     '#7c3aed', '#ede9fe'],
                    'requested'      => ['요청',     '#64748b', '#f1f5f9'],
                    'additional_dev' => ['추가개발', '#f59e0b', '#fef3c7'],
                    'ai_review'      => ['AI검토',   '#94a3b8', '#f1f5f9'],
                    default          => [$r->status, '#64748b', '#f1f5f9'],
                };
            @endphp
            <tr>
                <td>
                    <a href="{{ route('maint-requests.index', ['open' => $r->id]) }}"
                       style="font-family:monospace;font-weight:600;color:#6366f1;text-decoration:none;font-size:12px;"
                       target="_blank">#{{ $r->excel_no }}</a>
                </td>
                <td style="font-size:11px;color:#64748b;font-family:monospace;">
                    {{ optional($r->request_date)->format('Y-m-d') ?? '-' }}
                </td>
                <td>
                    <div style="font-size:12px;color:#334155;max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                         title="{{ $r->summary }}">{{ $r->summary }}</div>
                </td>
                <td style="font-size:11px;color:#64748b;">{{ optional($r->assignee)->name ?? '-' }}</td>
                <td style="text-align:center;">
                    @if($r->paid_dev_enabled)
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;background:#fef3c7;color:#92400e;">Pay</span>
                    @else
                        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;">추가개발</span>
                    @endif
                </td>
                <td style="text-align:right;font-size:12px;color:#334155;">
                    {{ $r->paid_dev_days ? $r->paid_dev_days.'일' : '-' }}
                </td>
                <td style="text-align:right;font-size:12px;color:#334155;font-family:monospace;">
                    {{ $r->paid_dev_cost ? '₩'.number_format($r->paid_dev_cost) : '-' }}
                </td>
                <td style="text-align:center;">
                    <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $statusLbl[2] }};color:{{ $statusLbl[1] }};">{{ $statusLbl[0] }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">
                    {{ $year }}년 유상개발 내역이 없습니다.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($items->isNotEmpty())
        <tfoot style="background:#fefce8;border-top:2px solid #fde68a;font-weight:700;">
            <tr>
                <td colspan="5" style="text-align:right;padding:10px 14px;font-size:12px;color:#92400e;">합계 (유상만)</td>
                <td style="text-align:right;padding:10px 14px;font-size:12px;color:#92400e;">{{ number_format($paidDays) }}일</td>
                <td style="text-align:right;padding:10px 14px;font-size:13px;color:#dc2626;font-family:monospace;">₩{{ number_format($paidCost) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

@endsection
