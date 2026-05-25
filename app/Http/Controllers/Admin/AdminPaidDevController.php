<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\Maint\MaintRequest;
use Illuminate\Http\Request;

/**
 * 관리자 → 유상개발 명세.
 *
 *   회사 (드롭다운) + 연도 (드롭다운) 으로 필터.
 *   대상: company_group_id 일치 + (paid_dev_enabled=true OR category='추가개발').
 *   연도: request_date 의 YEAR.
 */
class AdminPaidDevController extends Controller
{
    public function index(Request $request)
    {
        // 유상개발 또는 '추가개발' 카테고리 SR 이 있는 회사만 드롭다운에 노출
        $companies = CompanyGroup::query()
            ->whereIn('id', MaintRequest::query()
                ->where(function ($q) {
                    $q->where('paid_dev_enabled', true)->orWhere('category', '추가개발');
                })
                ->select('company_group_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        $companyId = (int) ($request->query('company_group_id') ?: ($companies->first()->id ?? 0));

        // 회사가 선택되지 않았으면 (혹은 유효 회사 없음) 빈 결과
        $base = MaintRequest::query()
            ->where('company_group_id', $companyId)
            ->where(function ($q) {
                $q->where('paid_dev_enabled', true)->orWhere('category', '추가개발');
            });

        $years = (clone $base)
            ->selectRaw('YEAR(request_date) as y')
            ->whereNotNull('request_date')
            ->groupBy('y')->orderByDesc('y')
            ->pluck('y')->map(fn($v) => (int) $v)->all();

        $year = (int) ($request->query('year') ?: ($years[0] ?? now()->year));

        $items = (clone $base)
            ->with('assignee:id,name')
            ->whereYear('request_date', $year)
            ->orderBy('request_date')
            ->orderBy('excel_no')
            ->get(['id', 'excel_no', 'summary', 'request_date', 'paid_dev_enabled', 'paid_dev_days', 'paid_dev_cost', 'category', 'status', 'assignee_id', 'paid_dev_sent_at']);

        $totalDays = (int) $items->sum(fn($r) => (int) ($r->paid_dev_days ?? 0));
        $totalCost = (int) $items->sum(fn($r) => (int) ($r->paid_dev_cost ?? 0));
        $paidOnly  = $items->where('paid_dev_enabled', true);
        $paidDays  = (int) $paidOnly->sum(fn($r) => (int) ($r->paid_dev_days ?? 0));
        $paidCost  = (int) $paidOnly->sum(fn($r) => (int) ($r->paid_dev_cost ?? 0));

        return view('admin.paid-dev.index', [
            'companies'  => $companies,
            'years'      => $years,
            'companyId'  => $companyId,
            'year'       => $year,
            'items'      => $items,
            'totalDays'  => $totalDays,
            'totalCost'  => $totalCost,
            'paidDays'   => $paidDays,
            'paidCost'   => $paidCost,
            'paidCount'  => $paidOnly->count(),
            'totalCount' => $items->count(),
        ]);
    }
}
