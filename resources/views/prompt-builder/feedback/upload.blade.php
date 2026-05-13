@extends('layouts.app')

@section('title', 'Prompt Builder - 결과 업로드')

@section('content')
<div style="max-width:640px;margin:0 auto;padding:32px 16px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('builder.feedback.index', $project) }}" style="color:#7c3aed;font-size:13px;text-decoration:none;">← 피드백 목록</a>
        <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:8px 0 0;">웍스 결과 업로드</h1>
        <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">외부 웍스 도구의 결과물을 업로드하여 프롬프트 개선에 활용합니다</p>
    </div>

    <form action="{{ route('builder.feedback.store', $project) }}" method="POST" enctype="multipart/form-data" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
        @csrf

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">빌더 선택 <span style="color:#ef4444;">*</span></label>
            <select name="builder_id" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                <option value="">빌더 선택...</option>
                @foreach($builders as $builder)
                <option value="{{ $builder->id }}">{{ $builder->title }} ({{ strtoupper($builder->ai_type) }} · v{{ $builder->current_version }})</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">업로드 방식 <span style="color:#ef4444;">*</span></label>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                @foreach([['file','파일'],['zip','ZIP'],['text','텍스트']] as [$val, $label])
                <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">
                    <input type="radio" name="upload_method" value="{{ $val }}" required>
                    <span style="font-size:13px;color:#374151;">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">결과 파일</label>
            <input type="file" name="archive" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">평가 (선택)</label>
            <div style="display:flex;gap:8px;">
                @for($i = 1; $i <= 5; $i++)
                <label style="cursor:pointer;">
                    <input type="radio" name="user_rating" value="{{ $i }}" style="display:none;">
                    <span style="font-size:24px;cursor:pointer;" title="{{ $i }}점">⭐</span>
                </label>
                @endfor
            </div>
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">메모 (선택)</label>
            <textarea name="user_memo" rows="4" placeholder="웍스 결과에 대한 메모를 작성하세요..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;resize:none;box-sizing:border-box;"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <a href="{{ route('builder.feedback.index', $project) }}" style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;color:#374151;text-decoration:none;">취소</a>
            <button type="submit" style="padding:10px 24px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">업로드</button>
        </div>
    </form>

</div>
@endsection
