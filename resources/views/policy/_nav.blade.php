<nav class="pnav">
    <a href="{{ route('policy.terms') }}" class="{{ $cur==='terms' ? 'cur' : '' }}">{{ __('policy.terms') }}</a>
    <a href="{{ route('policy.privacy') }}" class="{{ $cur==='privacy' ? 'cur' : '' }}">{{ __('policy.privacy') }}</a>
    <a href="{{ route('policy.cookie') }}" class="{{ $cur==='cookie' ? 'cur' : '' }}">{{ __('policy.cookie') }}</a>
    <a href="{{ route('policy.youth') }}" class="{{ $cur==='youth' ? 'cur' : '' }}">{{ __('policy.youth') }}</a>
</nav>
