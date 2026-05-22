<header class="ph">
    <div class="ph-inner">
        <a href="{{ url('/') }}" class="ph-logo">⚡ SupportWorks</a>
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="display:flex;gap:4px;">
                <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="locale" value="ko">
                    <button type="submit" style="padding:3px 8px;border-radius:5px;font-size:11px;font-weight:700;border:1px solid rgba(255,255,255,.2);background:{{ app()->getLocale()==='ko' ? 'rgba(167,139,250,.4)' : 'rgba(255,255,255,.08)' }};color:{{ app()->getLocale()==='ko' ? '#c4b5fd' : 'rgba(255,255,255,.35)' }};cursor:pointer;font-family:inherit;transition:all .15s;">KO</button>
                </form>
                <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="locale" value="en">
                    <button type="submit" style="padding:3px 8px;border-radius:5px;font-size:11px;font-weight:700;border:1px solid rgba(255,255,255,.2);background:{{ app()->getLocale()==='en' ? 'rgba(167,139,250,.4)' : 'rgba(255,255,255,.08)' }};color:{{ app()->getLocale()==='en' ? '#c4b5fd' : 'rgba(255,255,255,.35)' }};cursor:pointer;font-family:inherit;transition:all .15s;">EN</button>
                </form>
            </div>
            <a href="{{ url('/') }}" class="ph-back">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('policy.home') }}
            </a>
        </div>
    </div>
</header>
