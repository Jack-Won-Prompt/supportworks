<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app()->getLocale() === 'en' ? 'Account Deletion' : '계정 삭제 요청' }} — SupportWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('policy._style')
</head>
<body>
@include('policy._header')
<div class="pw">
    <h1 class="pt">{{ app()->getLocale() === 'en' ? 'Account Deletion Request' : '계정 삭제 요청' }}</h1>

@if(app()->getLocale() === 'en')
    <p class="pd">Last updated: June 9, 2026 &nbsp;|&nbsp; LinkTheLab Co., Ltd. — SupportWorks</p>

    <div class="info-box">
        <p><strong>Developer:</strong> LinkTheLab Co., Ltd.<br>
        <strong>App:</strong> SupportWorks (com.linkthelab.supportworks)<br>
        This page describes how to request deletion of your SupportWorks account and the associated personal data.</p>
    </div>

    <div class="ps">
        <h2>1. How to request deletion</h2>
        <p>Send an email to <a href="mailto:adm@linkthelab.co.kr?subject=SupportWorks%20Account%20Deletion%20Request" style="color:#7c3aed;"><strong>adm@linkthelab.co.kr</strong></a> with the subject line <em>"SupportWorks Account Deletion Request"</em>, including:</p>
        <ul>
            <li>The email address registered with your SupportWorks account</li>
            <li>Your full name as registered</li>
            <li>The company name your account is associated with</li>
        </ul>
        <p>For security, the request must be sent from the email address registered to the account. We may contact you for additional identity verification.</p>
        <p>We process deletion requests within <strong>30 days</strong> of verification.</p>
    </div>

    <div class="ps">
        <h2>2. Data that will be deleted</h2>
        <ul>
            <li>Account profile — name, email, password hash, phone number, job title, profile photo, company affiliation</li>
            <li>User-generated content owned by you — chat messages you sent, Plan-Do-Act notes you authored, SR (maintenance request) entries and notes you authored, attached files / images / voice recordings you uploaded</li>
            <li>Device tokens used for push notifications (FCM)</li>
            <li>Authentication tokens and active sessions</li>
            <li>Notification preferences and app settings</li>
        </ul>
    </div>

    <div class="ps">
        <h2>3. Data that may be retained</h2>
        <p>Certain records are retained where required by law or to protect legitimate interests of other users:</p>
        <table>
            <thead><tr><th>Data</th><th>Reason</th><th>Retention</th></tr></thead>
            <tbody>
                <tr><td>Messages and content within shared workspaces (visible to other team members) — references to the author are anonymized but content remains</td><td>To preserve the integrity of conversations and project records for remaining members</td><td>Until the workspace owner removes them</td></tr>
                <tr><td>Contract / payment / withdrawal records</td><td>E-Commerce Act (Korea)</td><td>5 years</td></tr>
                <tr><td>Consumer complaint / dispute records</td><td>E-Commerce Act (Korea)</td><td>3 years</td></tr>
                <tr><td>Access logs</td><td>Communications Secrets Protection Act (Korea)</td><td>3 months</td></tr>
            </tbody>
        </table>
        <p>After the retention period, this data is permanently deleted.</p>
    </div>

    <div class="ps">
        <h2>4. After deletion</h2>
        <ul>
            <li>Your account cannot be restored after deletion completes.</li>
            <li>You may register again with the same email, but past data will not be recovered.</li>
            <li>You will receive an email confirmation when deletion is complete.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>5. Contact</h2>
        <p>If you have questions about the deletion process, contact us at <a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a> or +82-2-1544-9086.</p>
        <p>For the full Privacy Policy, see <a href="{{ route('policy.privacy') }}" style="color:#7c3aed;">Privacy Policy</a>.</p>
    </div>

@else
    <p class="pd">최종 업데이트: 2026년 6월 9일 &nbsp;|&nbsp; 주식회사 링크더랩 — SupportWorks</p>

    <div class="info-box">
        <p><strong>개발자:</strong> 주식회사 링크더랩<br>
        <strong>앱:</strong> SupportWorks (com.linkthelab.supportworks)<br>
        본 페이지는 SupportWorks 계정 및 관련 개인정보 삭제 요청 방법을 안내합니다.</p>
    </div>

    <div class="ps">
        <h2>1. 삭제 요청 방법</h2>
        <p>아래 이메일 주소로 <em>"SupportWorks 계정 삭제 요청"</em> 제목으로 메일을 보내주세요.</p>
        <p style="margin:.75rem 0;"><a href="mailto:adm@linkthelab.co.kr?subject=SupportWorks%20%EA%B3%84%EC%A0%95%20%EC%82%AD%EC%A0%9C%20%EC%9A%94%EC%B2%AD" style="color:#7c3aed;font-weight:600;">adm@linkthelab.co.kr</a></p>
        <p>본문에 포함해 주세요:</p>
        <ul>
            <li>SupportWorks 가입 이메일 주소</li>
            <li>가입 시 등록한 이름</li>
            <li>소속 회사명</li>
        </ul>
        <p>본인 확인을 위해 <strong>가입한 이메일 주소</strong>에서 발송해야 합니다. 추가 인증을 위해 답장으로 추가 정보를 요청할 수 있습니다.</p>
        <p>본인 확인 완료 후 <strong>30일 이내</strong>에 처리합니다.</p>
    </div>

    <div class="ps">
        <h2>2. 삭제되는 데이터</h2>
        <ul>
            <li>계정 프로필 — 이름, 이메일, 비밀번호 해시, 연락처, 직책, 프로필 사진, 소속 회사 정보</li>
            <li>본인이 생성한 콘텐츠 — 작성한 채팅 메시지, Plan-Do-Act 노트, SR(유지보수 요청) 본문·노트, 업로드한 첨부 파일/이미지/음성 녹음</li>
            <li>푸시 알림용 디바이스 토큰(FCM)</li>
            <li>인증 토큰 및 활성 세션</li>
            <li>알림 설정 및 앱 환경설정</li>
        </ul>
    </div>

    <div class="ps">
        <h2>3. 보관되는 데이터</h2>
        <p>다른 이용자의 정당한 이익 보호 또는 법령상 의무에 따라 다음 데이터는 일정 기간 보관됩니다.</p>
        <table>
            <thead><tr><th>데이터</th><th>사유</th><th>보관 기간</th></tr></thead>
            <tbody>
                <tr><td>공유 워크스페이스 내 메시지 및 콘텐츠(다른 팀원이 확인 가능한 항목) — 작성자 표기는 익명화하되 본문은 유지</td><td>남은 구성원의 대화·프로젝트 기록 무결성 보호</td><td>워크스페이스 소유자가 삭제할 때까지</td></tr>
                <tr><td>계약·결제·청약 철회 기록</td><td>전자상거래법</td><td>5년</td></tr>
                <tr><td>소비자 불만·분쟁 기록</td><td>전자상거래법</td><td>3년</td></tr>
                <tr><td>접속 로그</td><td>통신비밀보호법</td><td>3개월</td></tr>
            </tbody>
        </table>
        <p>위 보관 기간 경과 후 영구 삭제됩니다.</p>
    </div>

    <div class="ps">
        <h2>4. 삭제 후 안내</h2>
        <ul>
            <li>삭제 완료 후 계정은 복구할 수 없습니다.</li>
            <li>동일 이메일로 재가입은 가능하나 과거 데이터는 복원되지 않습니다.</li>
            <li>삭제 처리 완료 시 이메일로 확인 메일을 발송합니다.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>5. 문의</h2>
        <p>삭제 절차에 대한 문의는 <a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a> 또는 02-1544-9086 으로 연락 주십시오.</p>
        <p>전체 개인정보처리방침은 <a href="{{ route('policy.privacy') }}" style="color:#7c3aed;">개인정보처리방침</a>을 참조하세요.</p>
    </div>
@endif

</div>
</body>
</html>
