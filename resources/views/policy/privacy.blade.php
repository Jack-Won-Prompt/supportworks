<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('policy.privacy_title') }} — SupportWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('policy._style')
</head>
<body>
@include('policy._header')
<div class="pw">
    @include('policy._nav', ['cur' => 'privacy'])
    <h1 class="pt">{{ __('policy.privacy_title') }}</h1>

@if(app()->getLocale() === 'en')
    <p class="pd">Effective Date: January 1, 2024 &nbsp;|&nbsp; LinkTheLab Co., Ltd.</p>

    <div class="info-box">
        <p>LinkTheLab Co., Ltd. establishes and discloses the following Privacy Policy to protect the personal information of data subjects and to handle related grievances promptly and smoothly, in accordance with Article 30 of the Personal Information Protection Act.</p>
    </div>

    <div class="ps">
        <h2>Article 1 (Purposes of Processing Personal Information)</h2>
        <p>The Company processes personal information for the following purposes. The processed personal information will not be used for purposes other than those listed below. If the purpose of use changes, the Company will seek prior consent.</p>
        <ul>
            <li><strong>Membership registration and management:</strong> Confirming intent to use the service, identifying and authenticating users, preventing unauthorized use</li>
            <li><strong>Service provision:</strong> Providing project collaboration, real-time messaging, and 웍스 assistant features</li>
            <li><strong>Customer support:</strong> Receiving and handling inquiries, resolving complaints</li>
            <li><strong>Service improvement:</strong> Developing new features, analyzing data to improve service quality</li>
            <li><strong>Marketing and advertising:</strong> Event notifications, newsletter delivery (with separate consent)</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 2 (Personal Information Items Processed)</h2>
        <table>
            <thead>
                <tr><th>Category</th><th>Items Collected</th><th>Collection Method</th></tr>
            </thead>
            <tbody>
                <tr><td>Required</td><td>Name, email address, password, company name</td><td>Direct input during registration</td></tr>
                <tr><td>Optional</td><td>Profile photo, phone number, job title</td><td>Direct input during profile setup</td></tr>
                <tr><td>Automatically collected</td><td>IP address, browser type, service usage records, cookies</td><td>Automatically collected when using the Service</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>Article 3 (Processing and Retention Period of Personal Information)</h2>
        <table>
            <thead>
                <tr><th>Items Retained</th><th>Basis for Retention</th><th>Retention Period</th></tr>
            </thead>
            <tbody>
                <tr><td>Member information</td><td>User consent</td><td>Until membership withdrawal</td></tr>
                <tr><td>Contract / withdrawal records</td><td>E-Commerce Act</td><td>5 years</td></tr>
                <tr><td>Payment and supply records</td><td>E-Commerce Act</td><td>5 years</td></tr>
                <tr><td>Consumer complaint / dispute records</td><td>E-Commerce Act</td><td>3 years</td></tr>
                <tr><td>Access logs</td><td>Communications Secrets Protection Act</td><td>3 months</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>Article 4 (Provision of Personal Information to Third Parties)</h2>
        <p>The Company does not, in principle, provide users' personal information to third parties. Exceptions are made in the following cases:</p>
        <ul>
            <li>When the user has given prior consent</li>
            <li>When required by law or by a law enforcement agency following procedures prescribed by law for investigative purposes</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 5 (Outsourcing of Personal Information Processing)</h2>
        <table>
            <thead>
                <tr><th>Processor</th><th>Outsourced Tasks</th><th>Retention Period</th></tr>
            </thead>
            <tbody>
                <tr><td>Amazon Web Services (AWS)</td><td>Server infrastructure and data storage</td><td>Until contract termination</td></tr>
                <tr><td>Pusher Ltd.</td><td>Real-time message delivery</td><td>Until contract termination</td></tr>
                <tr><td>Email delivery service</td><td>Sending service notification emails</td><td>Until contract termination</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>Article 6 (Rights and Obligations of Data Subjects and How to Exercise Them)</h2>
        <p>Users may exercise the following rights at any time:</p>
        <ul>
            <li>Request access to personal information</li>
            <li>Request correction if there are errors</li>
            <li>Request deletion</li>
            <li>Request suspension of processing</li>
        </ul>
        <p>To exercise these rights, please use the settings menu within the Service or contact us at <a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a>.</p>
    </div>

    <div class="ps">
        <h2>Article 7 (Destruction of Personal Information)</h2>
        <p>When personal information is no longer necessary—such as after the retention period has expired or the processing purpose has been achieved—the Company will destroy it without delay.</p>
        <ul>
            <li><strong>Electronic files:</strong> Permanently deleted using a method that makes recovery impossible</li>
            <li><strong>Paper documents:</strong> Shredded or incinerated</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 8 (Personal Information Protection Officer)</h2>
        <table>
            <tbody>
                <tr><td><strong>Name</strong></td><td>Yeon-A Choi</td></tr>
                <tr><td><strong>Title</strong></td><td>Chief Executive Officer</td></tr>
                <tr><td><strong>Email</strong></td><td><a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a></td></tr>
                <tr><td><strong>Phone</strong></td><td>02-1544-9086</td></tr>
            </tbody>
        </table>
        <p style="margin-top:.75rem;">Complaints and requests for remedy related to personal information processing may be submitted to the following agencies:</p>
        <ul>
            <li>Personal Information Infringement Report Center: <a href="https://privacy.kisa.or.kr" target="_blank" style="color:#7c3aed;">privacy.kisa.or.kr</a> (Call 118)</li>
            <li>Personal Information Dispute Mediation Committee: <a href="https://www.kopico.go.kr" target="_blank" style="color:#7c3aed;">www.kopico.go.kr</a> (1833-6972)</li>
            <li>Supreme Prosecutors' Office Cyber Investigation Division: (Call 1301)</li>
            <li>National Police Agency Cyber Safety Bureau: (Call 182)</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Addendum</h2>
        <p>This Policy shall take effect from January 1, 2024.</p>
        <p class="contact-line">Contact: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>

@else
    <p class="pd">시행일: 2024년 1월 1일 &nbsp;|&nbsp; 주식회사 링크더랩</p>

    <div class="info-box">
        <p>주식회사 링크더랩은 「개인정보 보호법」 제30조에 따라 정보주체의 개인정보를 보호하고 이와 관련한 고충을 신속하고 원활하게 처리할 수 있도록 다음과 같이 개인정보 처리방침을 수립·공개합니다.</p>
    </div>

    <div class="ps">
        <h2>제1조 (개인정보의 처리 목적)</h2>
        <p>회사는 다음 목적을 위해 개인정보를 처리합니다. 처리한 개인정보는 다음 목적 이외의 용도로는 이용되지 않으며, 이용 목적이 변경될 경우 사전에 동의를 구합니다.</p>
        <ul>
            <li><strong>회원 가입 및 관리:</strong> 서비스 이용 의사 확인, 이용자 식별·인증, 서비스 부정 이용 방지</li>
            <li><strong>서비스 제공:</strong> 프로젝트 협업, 실시간 메시지, 웍스 어시스턴트 기능 제공</li>
            <li><strong>고객 지원:</strong> 문의 접수·처리, 불만 해소</li>
            <li><strong>서비스 개선:</strong> 신규 기능 개발, 서비스 품질 향상을 위한 분석</li>
            <li><strong>마케팅·광고:</strong> 이벤트 안내, 뉴스레터 발송 (별도 동의 시)</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제2조 (처리하는 개인정보 항목)</h2>
        <table>
            <thead>
                <tr><th>구분</th><th>수집 항목</th><th>수집 방법</th></tr>
            </thead>
            <tbody>
                <tr><td>필수</td><td>이름, 이메일 주소, 비밀번호, 회사명</td><td>회원가입 시 직접 입력</td></tr>
                <tr><td>선택</td><td>프로필 사진, 연락처, 직책</td><td>프로필 설정 시 직접 입력</td></tr>
                <tr><td>자동 수집</td><td>접속 IP, 브라우저 종류, 서비스 이용 기록, 쿠키</td><td>서비스 이용 시 자동 수집</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>제3조 (개인정보의 처리 및 보유 기간)</h2>
        <table>
            <thead>
                <tr><th>보유 항목</th><th>보유 근거</th><th>보유 기간</th></tr>
            </thead>
            <tbody>
                <tr><td>회원 정보</td><td>이용자 동의</td><td>회원 탈퇴 시까지</td></tr>
                <tr><td>계약·청약 철회 기록</td><td>전자상거래법</td><td>5년</td></tr>
                <tr><td>대금 결제·공급 기록</td><td>전자상거래법</td><td>5년</td></tr>
                <tr><td>소비자 불만·분쟁 기록</td><td>전자상거래법</td><td>3년</td></tr>
                <tr><td>접속 로그</td><td>통신비밀보호법</td><td>3개월</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>제4조 (개인정보의 제3자 제공)</h2>
        <p>회사는 원칙적으로 이용자의 개인정보를 제3자에게 제공하지 않습니다. 다만, 다음의 경우에는 예외로 합니다.</p>
        <ul>
            <li>이용자가 사전에 동의한 경우</li>
            <li>법령에 의거하거나 수사 목적으로 법령에 정해진 절차와 방법에 따라 수사기관의 요구가 있는 경우</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제5조 (개인정보 처리의 위탁)</h2>
        <table>
            <thead>
                <tr><th>수탁업체</th><th>위탁 업무 내용</th><th>보유 기간</th></tr>
            </thead>
            <tbody>
                <tr><td>Amazon Web Services (AWS)</td><td>서버 인프라 및 데이터 저장</td><td>계약 종료 시까지</td></tr>
                <tr><td>Pusher Ltd.</td><td>실시간 메시지 전송 처리</td><td>계약 종료 시까지</td></tr>
                <tr><td>이메일 발송 서비스</td><td>서비스 알림 이메일 발송</td><td>계약 종료 시까지</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>제6조 (정보주체의 권리·의무 및 행사 방법)</h2>
        <p>이용자는 언제든지 다음의 권리를 행사할 수 있습니다.</p>
        <ul>
            <li>개인정보 열람 요구</li>
            <li>오류 등이 있을 경우 정정 요구</li>
            <li>삭제 요구</li>
            <li>처리 정지 요구</li>
        </ul>
        <p>위 권리 행사는 서비스 내 설정 메뉴 또는 <a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a>으로 문의하시면 처리해 드립니다.</p>
    </div>

    <div class="ps">
        <h2>제7조 (개인정보의 파기)</h2>
        <p>회사는 개인정보 보유 기간의 경과, 처리 목적 달성 등 개인정보가 불필요하게 되었을 때에는 지체 없이 해당 개인정보를 파기합니다.</p>
        <ul>
            <li><strong>전자적 파일 형태:</strong> 복구 불가능한 방법으로 영구 삭제</li>
            <li><strong>종이 문서:</strong> 분쇄기로 분쇄하거나 소각</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제8조 (개인정보 보호책임자)</h2>
        <table>
            <tbody>
                <tr><td><strong>성명</strong></td><td>최연아</td></tr>
                <tr><td><strong>직책</strong></td><td>대표이사</td></tr>
                <tr><td><strong>이메일</strong></td><td><a href="mailto:adm@linkthelab.co.kr" style="color:#7c3aed;">adm@linkthelab.co.kr</a></td></tr>
                <tr><td><strong>전화</strong></td><td>02-1544-9086</td></tr>
            </tbody>
        </table>
        <p style="margin-top:.75rem;">개인정보 처리에 관한 불만, 피해구제 요청은 아래 기관에 신청하실 수 있습니다.</p>
        <ul>
            <li>개인정보침해신고센터: <a href="https://privacy.kisa.or.kr" target="_blank" style="color:#7c3aed;">privacy.kisa.or.kr</a> (국번 없이 118)</li>
            <li>개인정보 분쟁조정위원회: <a href="https://www.kopico.go.kr" target="_blank" style="color:#7c3aed;">www.kopico.go.kr</a> (1833-6972)</li>
            <li>대검찰청 사이버수사과: (국번 없이 1301)</li>
            <li>경찰청 사이버안전국: (국번 없이 182)</li>
        </ul>
    </div>

    <div class="ps">
        <h2>부칙</h2>
        <p>본 방침은 2024년 1월 1일부터 시행합니다.</p>
        <p class="contact-line">문의: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>
@endif

</div>
</body>
</html>
