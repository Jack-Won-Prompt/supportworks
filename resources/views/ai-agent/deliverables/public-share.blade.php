<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $typeDef['name'] }} — {{ $project->name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Apple SD Gothic Neo', 'Malgun Gothic', 'Noto Sans KR', sans-serif;
    background: #f8f5ff;
    color: #1e1b2e;
}
.ps-header {
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    padding: 20px 32px;
    border-bottom: 1.5px solid #c4b5fd;
    display: flex;
    align-items: center;
    gap: 14px;
}
.ps-logo {
    font-size: 13px;
    font-weight: 800;
    color: #5b21b6;
    letter-spacing: -0.3px;
}
.ps-divider { color: #c4b5fd; }
.ps-title {
    font-size: 15px;
    font-weight: 700;
    color: #3730a3;
    flex: 1;
}
.ps-project {
    font-size: 11px;
    color: #6d28d9;
    background: rgba(124,58,237,.1);
    padding: 3px 10px;
    border-radius: 20px;
}
.ps-content { max-width: 900px; margin: 0 auto; padding: 32px 24px; }
.ps-step {
    background: #fff;
    border: 1.5px solid #e9e0ff;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
}
.ps-step-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: #faf5ff;
    border-bottom: 1px solid #ede8ff;
}
.ps-step-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #7c3aed;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ps-step-title {
    font-size: 13px;
    font-weight: 700;
    color: #3730a3;
    flex: 1;
}
.ps-step-desc {
    font-size: 11px;
    color: #94a3b8;
}
.ps-step-body { padding: 16px 20px; }
.ps-field { margin-bottom: 14px; }
.ps-field-label {
    font-size: 11px;
    font-weight: 700;
    color: #6d28d9;
    margin-bottom: 4px;
}
.ps-field-value {
    font-size: 13px;
    color: #1e1b2e;
    line-height: 1.75;
    word-break: break-word;
}
.ps-field-value h1 { font-size: 17px; font-weight: 800; margin: 0.8em 0 0.4em; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
.ps-field-value h2 { font-size: 15px; font-weight: 700; margin: 0.7em 0 0.35em; }
.ps-field-value h3 { font-size: 13.5px; font-weight: 700; margin: 0.6em 0 0.3em; }
.ps-field-value p  { margin: 0.4em 0; }
.ps-field-value ul, .ps-field-value ol { padding-left: 20px; margin: 0.4em 0; }
.ps-field-value li { margin: 0.2em 0; }
.ps-field-value code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-size: 12px; font-family: monospace; }
.ps-field-value pre { background: #f1f5f9; padding: 10px 13px; border-radius: 7px; overflow-x: auto; margin: 0.6em 0; }
.ps-field-value pre code { background: none; padding: 0; font-size: 12px; }
.ps-field-value blockquote { border-left: 3px solid #c4b5fd; margin: 0.5em 0; padding: 4px 12px; color: #64748b; background: #faf5ff; border-radius: 0 6px 6px 0; }
.ps-field-value table { border-collapse: collapse; width: 100%; font-size: 12.5px; margin: 0.6em 0; }
.ps-field-value th, .ps-field-value td { border: 1px solid #e2e8f0; padding: 5px 10px; text-align: left; }
.ps-field-value th { background: #f8fafc; font-weight: 700; }
.ps-field-value hr { border: none; border-top: 1px solid #e2e8f0; margin: 1em 0; }
.ps-field-value strong { font-weight: 700; }
.ps-field-value em { font-style: italic; color: #475569; }
.ps-empty {
    font-size: 12px;
    color: #94a3b8;
    font-style: italic;
}
.ps-footer {
    text-align: center;
    padding: 20px;
    font-size: 11px;
    color: #94a3b8;
    border-top: 1px solid #ede8ff;
    margin-top: 16px;
}
@media print {
    .ps-header { break-inside: avoid; }
    .ps-step { break-inside: avoid; }
}
</style>
</head>
<body>

<div class="ps-header">
    <span class="ps-logo">SupportWorks</span>
    <span class="ps-divider">·</span>
    <span class="ps-title">{{ $typeDef['name'] }} ({{ $typeDef['shortName'] }})</span>
    <span class="ps-project">{{ $project->name }}</span>
</div>

<div class="ps-content">

    @foreach($typeDef['steps'] as $step)
    @php
        $hasContent = false;
        foreach (($step['fields'] ?? []) as $field) {
            if ($deliverable->getStepValue($step['order'], $field['key'])) {
                $hasContent = true;
                break;
            }
        }
    @endphp

    <div class="ps-step">
        <div class="ps-step-header">
            <div class="ps-step-num">{{ $step['order'] }}</div>
            <div class="ps-step-title">{{ $step['title'] }}</div>
            @if($step['description'] ?? '')
            <div class="ps-step-desc">{{ $step['description'] }}</div>
            @endif
        </div>
        <div class="ps-step-body">
            @if(empty($step['fields']))
                <span class="ps-empty">{{ __('deliverables.ps_empty_step') }}</span>
            @else
                @foreach($step['fields'] as $field)
                @php
                    $val = $deliverable->getStepValue($step['order'], $field['key']);
                @endphp
                <div class="ps-field">
                    <div class="ps-field-label">{{ $field['label'] }}</div>
                    @if($val)
                        <div class="ps-field-value" data-md="{{ $val }}"></div>
                    @else
                        <div class="ps-empty">{{ __('deliverables.ps_empty_field') }}</div>
                    @endif
                </div>
                @endforeach
            @endif
        </div>
    </div>
    @endforeach

    <div class="ps-footer">
        {{ __('deliverables.ps_footer') }}
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js"></script>
<script>
document.querySelectorAll('.ps-field-value[data-md]').forEach(el => {
    const raw = el.dataset.md.trim();
    el.innerHTML = raw ? marked.parse(raw) : '';
});
</script>
</body>
</html>
