{{-- SR [웍스 요약 생성] Alpine 컴포넌트 (create / edit / embed 공용).
     @push('scripts') 사용 안 함 — embed.blade.php 는 layouts.app 을 extend 안
     하는 standalone HTML 이라 @stack('scripts') 가 없음. 인라인 출력해야 모든
     호출처(create.blade.php, _form.blade.php → show/embed)에서 안전.

     중복 정의 방지: window.srSummary 이미 있으면 skip. --}}
<script>
if (typeof window.srSummary !== 'function') {
    window.srSummary = function (cfg) {
        return {
            summary: cfg.initialSummary || '',
            contextIds: Array.isArray(cfg.initialContextIds) ? cfg.initialContextIds : [],
            loading: false,
            // 원본(summary/content) 이 마지막 요약 이후 수정됐는지. 수정되면 버튼 다시 노출.
            dirty: false,

            init() {
                // 폼 내 summary 입력 + content textarea 의 input 이벤트 감지 → dirty=true
                const form = this.$root.closest('form');
                if (!form) return;
                const onInput = () => { this.dirty = true; };
                form.querySelector('[name="summary"]')?.addEventListener('input', onInput);
                form.querySelector('[name="content"]')?.addEventListener('input', onInput);
            },

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
                    if (cfg.srId)          fd.append('id', cfg.srId);
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
                    this.dirty      = false; // 방금 새 요약을 만들었으니 원본 변경 없음
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
    };
}
</script>
<style>[x-cloak]{display:none !important}</style>
