@push('scripts')
<script>
// SR [웍스 요약 생성] Alpine 컴포넌트 (create / edit 공용).
// 폼 안의 입력값(summary/content/menu/category/priority) 을 모아 POST → 결과 텍스트로 미리보기.
function srSummary(cfg) {
    return {
        summary: cfg.initialSummary || '',
        contextIds: Array.isArray(cfg.initialContextIds) ? cfg.initialContextIds : [],
        loading: false,

        async generate() {
            const form = this.$root.closest('form');
            if (!form) { alert('폼을 찾을 수 없습니다.'); return; }

            const get = (name) => (form.querySelector('[name="' + name + '"]')?.value ?? '').trim();

            const summaryVal = get('summary');
            const contentVal = get('content');
            if (!summaryVal && !contentVal) {
                alert('요약 또는 상세 내용을 먼저 입력하세요.');
                return;
            }

            this.loading = true;
            try {
                const fd = new FormData();
                if (cfg.srId)         fd.append('id', cfg.srId);
                if (summaryVal)        fd.append('summary',   summaryVal);
                if (contentVal)        fd.append('content',   contentVal);
                if (get('menu_name'))  fd.append('menu_name', get('menu_name'));
                if (get('category'))   fd.append('category',  get('category'));
                if (get('priority'))   fd.append('priority',  get('priority'));

                const res = await fetch(cfg.endpoint, {
                    method:  'POST',
                    body:    fd,
                    headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
                });
                const d = await res.json().catch(() => ({}));
                if (!res.ok || !d.ok) {
                    alert(d.message || ('웍스 요약 생성 실패 (HTTP ' + res.status + ')'));
                    return;
                }
                this.summary    = d.summary    || '';
                this.contextIds = Array.isArray(d.context_ids) ? d.context_ids : [];
            } catch (e) {
                alert('웍스 요약 생성 실패: ' + (e.message || e));
            } finally {
                this.loading = false;
            }
        },

        reset() {
            this.summary    = '';
            this.contextIds = [];
        },
    };
}
</script>
<style>[x-cloak]{display:none !important}</style>
@endpush
