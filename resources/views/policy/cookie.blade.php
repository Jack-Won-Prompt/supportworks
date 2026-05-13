<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('policy.cookie_title') }} — SupportWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('policy._style')
</head>
<body>
@include('policy._header')
<div class="pw">
    @include('policy._nav', ['cur' => 'cookie'])
    <h1 class="pt">{{ __('policy.cookie_title') }}</h1>

    @if(app()->getLocale() === 'en')
    <p class="pd">Effective Date: January 1, 2024 &nbsp;|&nbsp; LinkTheLab Co., Ltd.</p>

    <div class="ps">
        <h2>Article 1 (What are Cookies?)</h2>
        <p>Cookies are small text files stored in your browser when you visit a website. SupportWorks uses cookies to provide smooth service delivery, enhance user experience, and maintain security.</p>
    </div>

    <div class="ps">
        <h2>Article 2 (Types of Cookies Used)</h2>
        <table>
            <thead>
                <tr><th>Type</th><th>Cookie Name (Examples)</th><th>Purpose</th><th>Retention Period</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Essential Cookies</strong></td>
                    <td>session, XSRF-TOKEN</td>
                    <td>Required for core service functions such as maintaining login sessions and preventing CSRF attacks</td>
                    <td>Until session ends or up to 2 hours</td>
                </tr>
                <tr>
                    <td><strong>Functional Cookies</strong></td>
                    <td>app-theme, sidebar-state</td>
                    <td>Stores personalization settings such as theme preferences and sidebar state</td>
                    <td>1 year</td>
                </tr>
                <tr>
                    <td><strong>Performance Cookies</strong></td>
                    <td>_ga, _gid</td>
                    <td>Collects service usage statistics, error analysis, and performance improvements (Google Analytics)</td>
                    <td>Up to 2 years</td>
                </tr>
                <tr>
                    <td><strong>Security Cookies</strong></td>
                    <td>remember_web_*</td>
                    <td>Maintains login state (when "Remember Me" is selected)</td>
                    <td>Up to 30 days</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>Article 3 (Cookie Settings and Opt-out)</h2>
        <p>You may allow or refuse cookies through your browser settings. However, refusing essential cookies may prevent some service features such as login from functioning properly.</p>
        <h3>How to Manage Cookies by Browser</h3>
        <ul>
            <li><strong>Chrome:</strong> Settings → Privacy and Security → Cookies and other site data</li>
            <li><strong>Edge:</strong> Settings → Cookies and site permissions → Manage and delete cookies and site data</li>
            <li><strong>Firefox:</strong> Settings → Privacy &amp; Security → Cookies and Site Data</li>
            <li><strong>Safari:</strong> Preferences → Privacy → Prevent cross-site tracking</li>
        </ul>
    </div>

    <div class="ps">
        <h2>Article 4 (Third-Party Cookies)</h2>
        <p>Cookies may be set through the following third-party services used by SupportWorks.</p>
        <table>
            <thead>
                <tr><th>Third Party</th><th>Purpose</th><th>Policy Link</th></tr>
            </thead>
            <tbody>
                <tr><td>Google Analytics</td><td>Service usage statistics analysis</td><td>Google Privacy Policy</td></tr>
                <tr><td>Pusher</td><td>Real-time communication features</td><td>Pusher Privacy Policy</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>Article 5 (Changes to Cookie Policy)</h2>
        <p>This Cookie Policy may be updated in accordance with changes in relevant laws or service updates. Any changes will be announced via in-service notices or email.</p>
    </div>

    <div class="ps">
        <h2>Addendum</h2>
        <p>This policy is effective from January 1, 2024.</p>
        <p class="contact-line">Contact: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>

    @else
    <p class="pd">시행일: 2024년 1월 1일 &nbsp;|&nbsp; 주식회사 링크더랩</p>

    <div class="ps">
        <h2>제1조 (쿠키란?)</h2>
        <p>쿠키(Cookie)는 웹사이트를 방문할 때 브라우저에 저장되는 작은 텍스트 파일입니다. SupportWorks는 서비스의 원활한 제공, 이용자 경험 향상, 보안 유지를 위해 쿠키를 사용합니다.</p>
    </div>

    <div class="ps">
        <h2>제2조 (사용하는 쿠키의 종류)</h2>
        <table>
            <thead>
                <tr><th>종류</th><th>쿠키명 (예시)</th><th>목적</th><th>보유 기간</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>필수 쿠키</strong></td>
                    <td>session, XSRF-TOKEN</td>
                    <td>로그인 세션 유지, CSRF 공격 방지 등 서비스의 핵심 기능 동작에 필요</td>
                    <td>세션 종료 시 또는 최대 2시간</td>
                </tr>
                <tr>
                    <td><strong>기능 쿠키</strong></td>
                    <td>app-theme, sidebar-state</td>
                    <td>이용자의 테마 설정, 사이드바 상태 등 개인화 설정 저장</td>
                    <td>1년</td>
                </tr>
                <tr>
                    <td><strong>성능 쿠키</strong></td>
                    <td>_ga, _gid</td>
                    <td>서비스 이용 통계 수집, 오류 분석 및 성능 개선 (Google Analytics)</td>
                    <td>최대 2년</td>
                </tr>
                <tr>
                    <td><strong>보안 쿠키</strong></td>
                    <td>remember_web_*</td>
                    <td>로그인 상태 유지 (자동 로그인 선택 시)</td>
                    <td>최대 30일</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>제3조 (쿠키 설정 및 거부)</h2>
        <p>이용자는 브라우저 설정을 통해 쿠키를 허용하거나 거부할 수 있습니다. 단, 필수 쿠키를 거부하면 로그인 등 서비스의 일부 기능이 정상적으로 동작하지 않을 수 있습니다.</p>
        <h3>브라우저별 쿠키 설정 방법</h3>
        <ul>
            <li><strong>Chrome:</strong> 설정 → 개인정보 및 보안 → 쿠키 및 기타 사이트 데이터</li>
            <li><strong>Edge:</strong> 설정 → 쿠키 및 사이트 권한 → 쿠키 및 사이트 데이터 관리</li>
            <li><strong>Firefox:</strong> 설정 → 개인 정보 및 보안 → 쿠키 및 사이트 데이터</li>
            <li><strong>Safari:</strong> 환경설정 → 개인 정보 보호 → 크로스 사이트 추적 방지</li>
        </ul>
    </div>

    <div class="ps">
        <h2>제4조 (제3자 쿠키)</h2>
        <p>SupportWorks는 아래와 같은 제3자 서비스를 통해 쿠키가 설정될 수 있습니다.</p>
        <table>
            <thead>
                <tr><th>제3자</th><th>목적</th><th>정책 링크</th></tr>
            </thead>
            <tbody>
                <tr><td>Google Analytics</td><td>서비스 이용 통계 분석</td><td>Google 개인정보처리방침</td></tr>
                <tr><td>Pusher</td><td>실시간 통신 기능 제공</td><td>Pusher 개인정보처리방침</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ps">
        <h2>제5조 (쿠키 정책 변경)</h2>
        <p>본 쿠키 정책은 관련 법령의 변경 또는 서비스 업데이트에 따라 변경될 수 있습니다. 변경 시 서비스 내 공지 또는 이메일을 통해 안내드립니다.</p>
    </div>

    <div class="ps">
        <h2>부칙</h2>
        <p>본 정책은 2024년 1월 1일부터 시행합니다.</p>
        <p class="contact-line">문의: <a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a> &nbsp;|&nbsp; 02-1544-9086</p>
    </div>
    @endif
</div>
</body>
</html>
