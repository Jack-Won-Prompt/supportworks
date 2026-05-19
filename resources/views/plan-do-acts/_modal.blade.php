@once
@php
    $pdaUserProjects = auth()->check()
        ? auth()->user()->projects()->orderBy('projects.name')->get(['projects.id', 'projects.name'])
        : collect();
@endphp
{{-- ════════ Plan-Do-Act 등록/수정 팝업 (공유 파셜) ════════ --}}
<div id="pda-modal" onclick="if(event.target===this)pdaClose()"
     style="display:none;position:fixed;inset:0;z-index:12000;background:rgba(15,10,40,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:640px;max-width:calc(100vw - 48px);max-height:88vh;border-radius:16px;box-shadow:0 24px 70px rgba(0,0,0,.32);display:flex;flex-direction:column;overflow:hidden;">
        {{-- 헤더 --}}
        <div style="padding:16px 22px;border-bottom:1px solid #f0eeff;display:flex;align-items:center;gap:10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <h3 id="pda-modal-title" style="margin:0;font-size:15px;font-weight:800;color:#1e1b2e;flex:1;">Plan-Do-Act 등록</h3>
            <button type="button" onclick="pdaClose()" style="background:none;border:none;font-size:24px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">&times;</button>
        </div>
        {{-- 본문 --}}
        <div style="padding:18px 22px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:14px;">
            <div id="pda-source-wrap" style="display:none;">
                <div style="font-size:11px;font-weight:700;color:#7c3aed;margin-bottom:5px;">원본 의견 · 답변</div>
                <div id="pda-source-box" style="background:#f8f7ff;border:1px solid #ece9ff;border-radius:9px;padding:10px 12px;font-size:12.5px;color:#4b5563;white-space:pre-wrap;word-break:break-word;line-height:1.6;max-height:160px;overflow-y:auto;"></div>
            </div>
            <div>
                <label class="pda-label">프로젝트</label>
                <select id="pda-project-sel" class="pda-input">
                    <option value="">(프로젝트 미지정)</option>
                    @foreach($pdaUserProjects as $pdaProj)
                        <option value="{{ $pdaProj->id }}">{{ $pdaProj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <div style="flex:1;min-width:0;">
                    <label class="pda-label">제목</label>
                    <input id="pda-title" type="text" class="pda-input" maxlength="255" placeholder="Plan-Do-Act 제목">
                </div>
                <div style="width:150px;flex-shrink:0;">
                    <label class="pda-label">상태</label>
                    <select id="pda-status" class="pda-input">
                        <option value="plan">계획 (Plan)</option>
                        <option value="do">실행 (Do)</option>
                        <option value="act">조치 (Act)</option>
                        <option value="done">완료</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="pda-label" style="color:#2563eb;">Plan · 계획</label>
                <textarea id="pda-plan" class="pda-input pda-textarea" placeholder="무엇을, 왜, 어떻게 할지 계획을 작성하세요"></textarea>
            </div>
            <div>
                <label class="pda-label" style="color:#b45309;">Do · 실행</label>
                <textarea id="pda-do" class="pda-input pda-textarea" placeholder="실제 수행한 내용을 작성하세요"></textarea>
            </div>
            <div>
                <label class="pda-label" style="color:#7c3aed;">Act · 조치</label>
                <textarea id="pda-act" class="pda-input pda-textarea" placeholder="결과를 검토하고 개선·조치 사항을 작성하세요"></textarea>
            </div>
        </div>
        {{-- 푸터 --}}
        <div style="padding:12px 22px;background:#faf9ff;border-top:1px solid #f0eeff;display:flex;align-items:center;gap:8px;">
            <button type="button" id="pda-delete-btn" onclick="pdaDelete()" style="display:none;padding:8px 14px;background:#fff;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;">삭제</button>
            <div style="flex:1;"></div>
            <button type="button" onclick="pdaClose()" style="padding:8px 16px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;">취소</button>
            <button type="button" id="pda-save-btn" onclick="pdaSave()" style="padding:8px 20px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;">저장</button>
        </div>
    </div>
</div>
<style>
#pda-modal .pda-label{display:block;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:5px;}
#pda-modal .pda-input{width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box;outline:none;color:#1f2937;background:#fff;}
#pda-modal .pda-input:focus{border-color:#a78bfa;}
#pda-modal .pda-textarea{min-height:72px;resize:vertical;line-height:1.55;}
</style>
<script>
(function(){
    let pdaId=null, pdaSourceCommentId=null, pdaSourceMessageId=null;
    const pdaCsrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';
    const pdaUrl='{{ url('plan-do-acts') }}';
    const $=(id)=>document.getElementById(id);

    function setProject(id, name){
        const sel=$('pda-project-sel');
        const pid=id?String(id):'';
        if(pid && name && !sel.querySelector(`option[value="${pid}"]`)){
            const o=document.createElement('option'); o.value=pid; o.textContent=name; sel.appendChild(o);
        }
        sel.value=pid;
    }

    function fill(d){
        $('pda-title').value=d.title||'';
        $('pda-status').value=d.status||'plan';
        $('pda-plan').value=d.plan||'';
        $('pda-do').value=d.do||'';
        $('pda-act').value=d.act||'';
        setProject(d.project_id||d.projectId||'', d.project?d.project.name:(d.project_name||''));
        const src=d.source_excerpt||'';
        if(src){ $('pda-source-box').textContent=src; $('pda-source-wrap').style.display='block'; }
        else { $('pda-source-wrap').style.display='none'; }
    }

    window.pdaOpenCreate=function(projectId,prefill){
        prefill=prefill||{};
        pdaId=null;
        pdaSourceCommentId=prefill.source_file_comment_id||null;
        pdaSourceMessageId=prefill.source_message_id||null;
        $('pda-modal-title').textContent='Plan-Do-Act 등록';
        $('pda-delete-btn').style.display='none';
        fill(Object.assign({}, prefill, { projectId: projectId||'' }));
        $('pda-modal').style.display='flex';
        $('pda-title').focus();
    };

    window.pdaOpenEdit=function(id){
        pdaId=id;
        $('pda-modal-title').textContent='Plan-Do-Act 수정';
        fill({}); $('pda-source-wrap').style.display='none';
        $('pda-delete-btn').style.display='none';
        $('pda-modal').style.display='flex';
        fetch(`${pdaUrl}/${id}`,{headers:{'Accept':'application/json'}})
            .then(r=>r.ok?r.json():Promise.reject())
            .then(d=>{
                pdaSourceCommentId=d.source_file_comment_id||null;
                pdaSourceMessageId=d.source_message_id||null;
                fill(d);
                $('pda-delete-btn').style.display='inline-block';
            })
            .catch(()=>{ alert('Plan-Do-Act를 불러오지 못했습니다.'); pdaClose(); });
    };

    window.pdaClose=function(){ $('pda-modal').style.display='none'; };

    window.pdaSave=function(){
        const title=$('pda-title').value.trim();
        if(!title){ alert('제목을 입력하세요.'); $('pda-title').focus(); return; }
        const btn=$('pda-save-btn'); btn.disabled=true; btn.textContent='저장 중…';
        const isEdit=!!pdaId;
        const payload={
            title,
            status:$('pda-status').value,
            plan:$('pda-plan').value,
            do:$('pda-do').value,
            act:$('pda-act').value,
            project_id:$('pda-project-sel').value||null,
        };
        if(!isEdit){
            payload.source_file_comment_id=pdaSourceCommentId;
            payload.source_message_id=pdaSourceMessageId;
        }
        const url=isEdit?`${pdaUrl}/${pdaId}`:pdaUrl;
        fetch(url,{method:isEdit?'PATCH':'POST',headers:{'X-CSRF-TOKEN':pdaCsrf(),'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(payload)})
            .then(async r=>{
                const d=await r.json().catch(()=>({}));
                if(!r.ok){
                    if(r.status===409&&d.plan_do_act_id){ alert(d.message||'이미 등록되어 있습니다.'); pdaClose(); window.pdaOpenEdit(d.plan_do_act_id); return; }
                    throw new Error(d.message||'저장에 실패했습니다.');
                }
                const sc=pdaSourceCommentId, sm=pdaSourceMessageId;
                pdaClose();
                if(typeof window.pdaOnSaved==='function') window.pdaOnSaved(d.item||null,isEdit?'update':'create',sc,sm);
                else location.reload();
            })
            .catch(e=>{ alert(e.message||'저장에 실패했습니다.'); })
            .finally(()=>{ btn.disabled=false; btn.textContent='저장'; });
    };

    window.pdaDelete=function(){
        if(!pdaId) return;
        if(!confirm('이 Plan-Do-Act를 삭제할까요?')) return;
        const sc=pdaSourceCommentId, sm=pdaSourceMessageId, id=pdaId;
        fetch(`${pdaUrl}/${pdaId}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':pdaCsrf(),'Accept':'application/json'}})
            .then(async r=>{ const d=await r.json().catch(()=>({})); if(!r.ok) throw new Error(d.message||'삭제에 실패했습니다.');
                pdaClose();
                if(typeof window.pdaOnDeleted==='function') window.pdaOnDeleted(id,sc,sm);
                else location.reload();
            })
            .catch(e=>alert(e.message||'삭제에 실패했습니다.'));
    };

    // ESC 닫기 (캡처 단계 — 하위 모달의 ESC 핸들러보다 먼저 처리)
    document.addEventListener('keydown',function(e){
        if(e.key==='Escape' && $('pda-modal') && $('pda-modal').style.display==='flex'){
            e.stopImmediatePropagation(); pdaClose();
        }
    }, true);
})();
</script>
@endonce
