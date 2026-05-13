<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('policy.terms_title') }} — SupportWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('policy._style')
</head>
<body>
@include('policy._header')
<div class="pw">
    @include('policy._nav', ['cur' => 'terms'])
    <h1 class="pt">{{ __('policy.terms_title') }}</h1>

@if(app()->getLocale() === 'en')
    <p class="pd">Effective Date: January 1, 2024 &nbsp;|&nbsp; LinkTheLab Co., Ltd.</p>

    <div class="ps">
        <h2>Article 1 (Purpose)</h2>
        <p>These Terms of Service govern the rights, obligations, and responsibilities between LinkTheLab Co., Ltd. (the "Company") and users in connection with the use of the SupportWorks service (the "Service") provided by the Company.</p>
    </div>

    <div class="ps">
        <h2>Article 2 (Definitions)</h2>
        <ul>
            <li><strong>"Service"</strong> means the 웍스-powered team collaboration platform SupportWorks and all related services provided by the Company.</li>
            <li><strong>"User"</strong> means any individual or legal entity who agrees to these Terms and uses the Service.</li>
            <li><strong>"Account"</strong> means the combination of email and password set by the User to access the Service.</li>
            <li><strong>"Content"</strong> means all forms of information, including text, files, and images, posted or uploaded by the User while using the Service.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 3 (Posting and Amendment of Terms)</h2>
        <ul>
            <li>The Company shall post the content of these Terms on the initial screen or a connected screen of the Service.</li>
            <li>The Company may amend these Terms to the extent that doing so does not violate applicable laws.</li>
            <li>Amendments will be announced at least 7 days before the effective date. Changes unfavorable to Users will be announced at least 30 days in advance.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 4 (Formation of Service Agreement)</h2>
        <p>A service agreement is formed when the User agrees to these Terms and applies for registration, and the Company accepts the application. The Company may refuse acceptance in the following cases:</p>
        <ul>
            <li>Using a false name or another person's identity</li>
            <li>Providing false information</li>
            <li>User is under 14 years of age</li>
            <li>Application requirements are otherwise not met</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 5 (Services Provided)</h2>
        <p>The Company provides the following services:</p>
        <ul>
            <li>Project management and collaboration tools</li>
            <li>Real-time messaging and customer inquiry handling</li>
            <li>웍스-powered work automation and assistant</li>
            <li>Schedule, task, and action item management</li>
            <li>Team community and announcement features</li>
            <li>Other services additionally developed or provided through partnerships</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 6 (User Obligations)</h2>
        <p>Users must not engage in the following activities:</p>
        <ul>
            <li>Misappropriating another person's information or registering false information</li>
            <li>Interfering with the operation of the Service</li>
            <li>Infringing intellectual property rights</li>
            <li>Posting obscene, violent, or hateful content</li>
            <li>Sending spam messages or distributing malware</li>
            <li>Any other activity that violates applicable laws</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 7 (Restriction of Use and Termination)</h2>
        <ul>
            <li>The Company may restrict use or terminate the agreement if the User violates Article 6.</li>
            <li>Users may request account deletion at any time through the settings menu within the Service.</li>
            <li>Upon deletion, personal information will be destroyed without delay, except as required by applicable law.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 8 (Limitation of Liability)</h2>
        <ul>
            <li>The Company is exempt from liability when the Service cannot be provided due to force majeure or acts of God.</li>
            <li>The Company is not liable for service disruptions caused by the User's own fault.</li>
            <li>The Company does not guarantee the reliability or accuracy of information posted by Users through the Service.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 9 (Dispute Resolution)</h2>
        <p>Disputes arising in connection with these Terms shall be governed by the laws of the Republic of Korea, and if litigation is initiated, the court having jurisdiction over the Company's headquarters shall serve as the court of first instance.</p>
    </div>

    <div class="ps">
        <h2>Addendum</h2>
        <p>These Terms shall take effect from January 1, 2024.</p>
        <p class="contact-line">Contact: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>

@else
    <p class="pd">시행일: 2024년 1월 1일 &nbsp;|&nbsp; 주식회사 링크더랩</p>

    <div class="ps">
        <h2>제1조 (목적)</h2>
        <p>본 약관은 주식회사 링크더랩(이하 "회사")이 제공하는 SupportWorks 서비스(이하 "서비스")의 이용과 관련하여 회사와 이용자 간의 권리·의무 및 책임사항을 규정함을 목적으로 합니다.</p>
    </div>

    <div class="ps">
        <h2>제2조 (정의)</h2>
        <ul>
            <li><strong>"서비스"</strong>란 회사가 제공하는 웍스 기반 팀 협업 플랫폼 SupportWorks 및 관련 제반 서비스를 의미합니다.</li>
            <li><strong>"이용자"</strong>란 본 약관에 동의하고 서비스를 이용하는 개인 또는 법인을 말합니다.</li>
            <li><strong>"계정"</strong>이란 이용자가 서비스에 접근하기 위해 설정한 이메일과 비밀번호의 조합을 의미합니다.</li>
            <li><strong>"콘텐츠"</strong>란 이용자가 서비스를 이용하는 과정에서 게시하거나 업로드한 텍스트, 파일, 이미지 등 모든 형태의 정보를 의미합니다.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제3조 (약관의 게시와 개정)</h2>
        <ul>
            <li>회사는 본 약관의 내용을 서비스 초기화면 또는 연결 화면을 통해 공지합니다.</li>
            <li>회사는 관련 법령을 위반하지 않는 범위 내에서 본 약관을 개정할 수 있습니다.</li>
            <li>약관 개정 시 시행일 7일 전부터 공지합니다. 이용자에게 불리한 변경은 30일 전부터 공지합니다.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제4조 (이용계약의 체결)</h2>
        <p>이용계약은 이용자가 본 약관에 동의하고 회원가입을 신청하면 회사가 이를 승낙함으로써 체결됩니다. 다음의 경우 승낙하지 않을 수 있습니다.</p>
        <ul>
            <li>실명이 아니거나 타인의 명의를 이용한 경우</li>
            <li>허위 정보를 기재한 경우</li>
            <li>만 14세 미만인 경우</li>
            <li>기타 이용 신청 요건이 미비된 경우</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제5조 (서비스의 제공)</h2>
        <p>회사는 다음과 같은 서비스를 제공합니다.</p>
        <ul>
            <li>프로젝트 관리 및 협업 도구</li>
            <li>실시간 메시지 및 고객 문의 처리</li>
            <li>웍스 기반 업무 자동화 및 어시스턴트</li>
            <li>일정·태스크·액션 아이템 관리</li>
            <li>팀 커뮤니티 및 공지 기능</li>
            <li>기타 회사가 추가 개발하거나 제휴를 통해 제공하는 서비스</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제6조 (이용자의 의무)</h2>
        <p>이용자는 다음 행위를 해서는 안 됩니다.</p>
        <ul>
            <li>타인의 정보 도용 또는 허위 정보 등록</li>
            <li>서비스 운영을 방해하는 행위</li>
            <li>지식재산권을 침해하는 행위</li>
            <li>음란·폭력적·혐오적 콘텐츠 게시</li>
            <li>스팸 메시지 발송 및 악성코드 유포</li>
            <li>관련 법령에 위반되는 행위</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제7조 (서비스 이용 제한 및 계약 해지)</h2>
        <ul>
            <li>회사는 이용자가 제6조를 위반할 경우 서비스 이용을 제한하거나 계약을 해지할 수 있습니다.</li>
            <li>이용자는 언제든지 서비스 내 설정 메뉴를 통해 탈퇴를 신청할 수 있습니다.</li>
            <li>탈퇴 시 관련 법령에서 정한 기간을 제외하고 이용자의 개인정보는 지체 없이 파기합니다.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제8조 (책임의 한계)</h2>
        <ul>
            <li>회사는 천재지변, 불가항력적 사유로 서비스를 제공할 수 없는 경우 책임이 면제됩니다.</li>
            <li>회사는 이용자의 귀책사유로 발생한 서비스 이용 장애에 대해 책임지지 않습니다.</li>
            <li>회사는 이용자가 서비스를 통해 게시한 정보의 신뢰성·정확성을 보증하지 않습니다.</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제9조 (분쟁 해결)</h2>
        <p>본 약관과 관련된 분쟁은 대한민국 법률에 따르며, 소송이 제기될 경우 회사의 본사 소재지를 관할하는 법원을 제1심 관할 법원으로 합니다.</p>
    </div>

    <div class="ps">
        <h2>부칙</h2>
        <p>본 약관은 2024년 1월 1일부터 시행합니다.</p>
        <p class="contact-line">문의: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>
@endif

</div>
</body>
</html>
