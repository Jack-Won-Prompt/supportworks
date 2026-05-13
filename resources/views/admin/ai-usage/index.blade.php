@extends('layouts.admin')
@section('title', '웍스 에이전트 사용량')

@section('content')

{{-- 기간 필터 --}}
<form method="GET" action="{{ route('admin.ai-usage.index') }}" id="filter-form">
<div class="filter-bar" style="margin-bottom:16px;">
    <div style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fff;">
        @foreach(['7'=>'7일','30'=>'30일','90'=>'90일','all'=>'전체'] as $val => $lbl)
        <a href="#" onclick="setPeriod('{{ $val }}');return false;"
           style="padding:6px 14px;font-size:12px;font-weight:500;text-decoration:none;transition:all .12s;{{ $period==$val ? 'background:#6366f1;color:#fff;' : 'color:#64748b;' }}">{{ $lbl }}</a>
        @endforeach
    </div>
    <input type="hidden" name="period" id="period-input" value="{{ $period }}">

    <select name="group_id" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;">
        <option value="">전체 회사</option>
        @foreach($groups as $g)
        <option value="{{ $g->id }}" {{ request('group_id')==$g->id ? 'selected':'' }}>{{ $g->name }}</option>
        @endforeach
    </select>

    <select name="model" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;">
        <option value="">전체 모델</option>
        @foreach($models as $m)
        <option value="{{ $m }}" {{ request('model')==$m ? 'selected':'' }}>{{ $m }}</option>
        @endforeach
    </select>

    <select name="status" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;">
        <option value="">전체 상태</option>
        <option value="success" {{ request('status')=='success' ? 'selected':'' }}>성공</option>
        <option value="error"   {{ request('status')=='error'   ? 'selected':'' }}>오류</option>
    </select>

    @if(request()->hasAny(['group_id','model','status']))
    <a href="{{ route('admin.ai-usage.index', ['period'=>$period]) }}" class="btn-secondary">초기화</a>
    @endif
</div>
</form>

{{-- 통계 카드 --}}
<div class="admin-stat-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px;">
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#6366f1;">{{ number_format($stats['total_cost'], 4) }}</div>
        <div class="admin-stat-lbl">총 비용 (USD)</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#0891b2;">{{ number_format($stats['total_calls']) }}</div>
        <div class="admin-stat-lbl">총 호출 수</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#16a34a;font-size:18px;">{{ number_format($stats['input_tokens']) }}</div>
        <div class="admin-stat-lbl">입력 토큰</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#7c3aed;font-size:18px;">{{ number_format($stats['output_tokens']) }}</div>
        <div class="admin-stat-lbl">출력 토큰</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#dc2626;">{{ number_format($stats['error_count']) }}</div>
        <div class="admin-stat-lbl">오류 수</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    {{-- 모델별 통계 --}}
    <div class="admin-card">
        <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:12px;">모델별 사용량</div>
        @if($byModel->isEmpty())
        <div style="color:#94a3b8;font-size:13px;text-align:center;padding:20px 0;">데이터 없음</div>
        @else
        <table class="admin-table" style="font-size:12px;">
            <thead><tr><th>모델</th><th style="text-align:right;">호출</th><th style="text-align:right;">토큰</th><th style="text-align:right;">비용(USD)</th></tr></thead>
            <tbody>
                @foreach($byModel as $row)
                <tr>
                    <td><span style="font-family:monospace;font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:4px;">{{ $row->model }}</span></td>
                    <td style="text-align:right;color:#334155;">{{ number_format($row->calls) }}</td>
                    <td style="text-align:right;color:#334155;">{{ number_format($row->tokens) }}</td>
                    <td style="text-align:right;color:#6366f1;font-weight:600;">${{ number_format($row->cost, 4) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- 프로젝트별 top 10 --}}
    <div class="admin-card">
        <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:12px;">프로젝트별 사용량 (Top 10)</div>
        @if($byProject->isEmpty())
        <div style="color:#94a3b8;font-size:13px;text-align:center;padding:20px 0;">데이터 없음</div>
        @else
        <table class="admin-table" style="font-size:12px;">
            <thead><tr><th>프로젝트</th><th style="text-align:right;">호출</th><th style="text-align:right;">비용(USD)</th></tr></thead>
            <tbody>
                @foreach($byProject as $row)
                <tr>
                    <td>
                        <div style="font-weight:500;color:#1e293b;">{{ $row->project?->name ?? '(삭제됨)' }}</div>
                        @if($row->project?->companyGroup)
                        <div style="font-size:11px;color:#94a3b8;">{{ $row->project->companyGroup->name }}</div>
                        @endif
                    </td>
                    <td style="text-align:right;color:#334155;">{{ number_format($row->calls) }}</td>
                    <td style="text-align:right;color:#6366f1;font-weight:600;">${{ number_format($row->cost, 4) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- 상세 로그 --}}
<div class="admin-card" style="padding:0;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;color:#1e293b;">
        호출 로그 (최신순)
        <span style="font-weight:400;color:#94a3b8;margin-left:8px;">{{ $logs->total() }}건</span>
    </div>
    @if($logs->isEmpty())
    <div style="text-align:center;padding:48px 0;color:#94a3b8;font-size:14px;">로그가 없습니다.</div>
    @else
    <table class="admin-table">
        <thead>
            <tr>
                <th>일시</th>
                <th>프로젝트</th>
                <th>사용자</th>
                <th>모델</th>
                <th>스테이지</th>
                <th style="text-align:right;">입력</th>
                <th style="text-align:right;">출력</th>
                <th style="text-align:right;">비용(USD)</th>
                <th style="text-align:right;">소요(ms)</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td style="font-size:11.5px;color:#94a3b8;white-space:nowrap;">{{ $log->created_at->format('m.d H:i:s') }}</td>
                <td style="font-size:12px;color:#334155;">{{ $log->project?->name ?? '—' }}</td>
                <td style="font-size:12px;color:#334155;">{{ $log->user?->name ?? '—' }}</td>
                <td><span style="font-family:monospace;font-size:10.5px;background:#f1f5f9;padding:2px 5px;border-radius:4px;">{{ Str::after($log->model, 'claude-') ?: $log->model }}</span></td>
                <td style="font-size:12px;color:#64748b;">{{ $log->stage ?? '—' }}</td>
                <td style="text-align:right;font-size:12px;color:#334155;">{{ number_format($log->input_tokens) }}</td>
                <td style="text-align:right;font-size:12px;color:#334155;">{{ number_format($log->output_tokens) }}</td>
                <td style="text-align:right;font-size:12px;color:#6366f1;font-weight:600;">${{ number_format($log->cost_usd, 5) }}</td>
                <td style="text-align:right;font-size:12px;color:#64748b;">{{ number_format($log->duration_ms) }}</td>
                <td>
                    @if($log->status === 'success')
                    <span class="badge badge-green">성공</span>
                    @elseif($log->status === 'error')
                    <span class="badge badge-red" title="{{ $log->error_message }}">오류</span>
                    @else
                    <span class="badge badge-gray">{{ $log->status }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($logs->hasPages())
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;display:flex;justify-content:center;">
        {{ $logs->links() }}
    </div>
    @endif
    @endif
</div>

<script>
function setPeriod(val) {
    document.getElementById('period-input').value = val;
    document.getElementById('filter-form').submit();
}
</script>

@endsection
