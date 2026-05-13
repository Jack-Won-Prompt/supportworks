<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('policy.youth_title') }} — SupportWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('policy._style')
</head>
<body>
@include('policy._header')
<div class="pw">
    @include('policy._nav', ['cur' => 'youth'])
    <h1 class="pt">{{ __('policy.youth_title') }}</h1>
    <p class="pd">시행일: 2024년 1월 1일 &nbsp;|&nbsp; 주식회사 링크더랩</p>

    <div class="info-box">
        <p>주식회사 링크더랩은 「청소년 보호법」에 따라 청소년이 유해한 환경에 노출되지 않도록 최선의 노력을 다하고 있습니다.</p>
    </div>

    <div class="ps">
        <h2>제1조 (청소년 유해 매체물 차단)</h2>
        <p>SupportWorks는 기업 협업 플랫폼으로, 청소년 유해 콘텐츠를 게시하거나 유통하지 않습니다. 또한 이용자가 유해한 콘텐츠를 게시하는 경우 즉시 삭제하고 해당 계정을 제재합니다.</p>
    </div>

    <div class="ps">
        <h2>제2조 (서비스 이용 연령 제한)</h2>
        <ul>
            <li>SupportWorks 서비스는 만 14세 이상의 이용자를 대상으로 합니다.</li>
            <li>회원가입 시 만 14세 미만으로 확인될 경우 이용을 제한합니다.</li>
            <li>만 14세 이상 만 19세 미만의 미성년자가 서비스를 이용하는 경우 법정대리인의 동의가 필요할 수 있습니다.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제3조 (유해 콘텐츠 신고 및 처리)</h2>
        <p>청소년에게 유해하다고 판단되는 콘텐츠를 발견하신 경우 아래 방법으로 신고해 주세요.</p>
        <ul>
            <li>이메일: <a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a></li>
            <li>전화: 02-1544-9086 (평일 09:00 – 18:00)</li>
        </ul>
        <p>신고 접수 후 영업일 기준 3일 이내에 처리 결과를 안내드립니다.</p>
    </div>

    <div class="ps">
        <h2>제4조 (청소년 보호 담당자)</h2>
        <table>
            <tbody>
                <tr><td><strong>담당자</strong></td><td>최연아 (대표이사)</td></tr>
                <tr><td><strong>이메일</strong></td><td><a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a></td></tr>
                <tr><td><strong>전화</strong></td><td>02-1544-9086</td></tr>
                <tr><td><strong>주소</strong></td><td>서울특별시 영등포구 경인로77길 49, 109동 2층 201-60호</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>제5조 (외부 기관 연락처)</h2>
        <ul>
            <li>여성가족부 청소년보호과: 02-2100-6000</li>
            <li>방송통신심의위원회: <a href="https://www.kocsc.or.kr" target="_blank" style="color:#7c3aed;">www.kocsc.or.kr</a> (1377)</li>
            <li>청소년사이버상담센터: <a href="https://www.cyber1388.kr" target="_blank" style="color:#7c3aed;">www.cyber1388.kr</a> (1388)</li>
        </ul>
    </div>

    <div class="ps">
        <h2>부칙</h2>
        <p>본 정책은 2024년 1월 1일부터 시행합니다.</p>
        <p class="contact-line">문의: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>
</div>
</body>
</html>
