{{--
  page-actions partial — reusable action button bar.

  Usage option A (section slot, rendered by ai-agent layout automatically):
    @section('page-actions')
        <button class="btn btn-sm btn-primary">저장</button>
        <button class="btn btn-sm btn-secondary">취소</button>
    @endsection

  Usage option B (direct include with $actions array):
    @include('ai-agent.partials.page-actions', [
        'actions' => [
            ['label' => '저장', 'href' => '#', 'primary' => true],
            ['label' => '취소', 'href' => '#', 'primary' => false],
        ]
    ])
--}}
@if(!empty($actions))
<div class="aia-page-actions">
    @foreach($actions as $action)
        <a href="{{ $action['href'] ?? '#' }}"
           style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;
                  {{ ($action['primary'] ?? true) ? 'background:var(--t600);color:#fff;' : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;' }}">
            {{ $action['label'] }}
        </a>
    @endforeach
</div>
@endif
