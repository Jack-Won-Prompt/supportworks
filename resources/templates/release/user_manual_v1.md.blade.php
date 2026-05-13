# {{ $project['name'] }} 사용자 매뉴얼

> 생성일: {{ $metadata['generated_at']->format('Y년 m월 d일') }}
> 버전: {{ $metadata['version'] }}

---

## 1. 시작하기

이 매뉴얼은 **{{ $project['name'] }}** 시스템을 처음 사용하시는 분을 위한 안내서입니다.
궁금한 사항이 있으시면 시스템 관리자에게 문의하세요.

### 1.1 시스템 접속

웹브라우저(Chrome, Edge, Firefox 등)를 열고 시스템 URL에 접속하세요.
접속 URL은 시스템 관리자에게 문의하세요.

### 1.2 로그인

접속 화면에서 아이디(이메일)와 비밀번호를 입력하고 **[로그인]** 버튼을 클릭하세요.

- 계정이 없다면 **[회원가입]** 을 먼저 진행하세요.
- 비밀번호를 잊어버린 경우 **[비밀번호 찾기]** 를 이용하세요.

@if(!empty($roles))
### 1.3 권한별 사용 안내

이 시스템은 다음 권한 그룹별로 사용할 수 있는 기능이 다릅니다:

@foreach($roles as $role)
- **{{ $role['name'] }}**@if($role['description']): {{ $role['description'] }}@endif

@endforeach
@endif

---

## 2. 화면별 사용 방법

@forelse($screens as $screen)
### {{ $screen['id'] }} {{ $screen['title'] }}

@if($screen['figma_image_url'])
![{{ $screen['title'] }} 화면]({{ $screen['figma_image_url'] }})

@endif
@if($screen['description'])
{{ $screen['description'] }}

@endif
#### 이 화면에서 무엇을 할 수 있나요?

@if(!empty($screen['requirements']))
@foreach($screen['requirements'] as $req)
- **{{ $req['title'] }}**@if($req['description']): {{ $req['description'] }}@endif

@endforeach
@else
이 화면의 상세 기능 안내는 추후 보강됩니다. 시스템 관리자에게 문의하세요.
@endif

@if(!empty($screen['required_permissions']))
#### 접근 권한

이 화면을 사용하려면 다음 권한이 필요합니다:

@foreach($screen['required_permissions'] as $perm)
- {{ $perm }}
@endforeach

@endif
@if($screen['flow']['previous'] || $screen['flow']['next'])
#### 화면 흐름

@if($screen['flow']['previous'])
- **이전 화면**: {{ $screen['flow']['previous']['id'] }} — {{ $screen['flow']['previous']['title'] }}
@endif
@if($screen['flow']['next'])
- **다음 화면**: {{ $screen['flow']['next']['id'] }} — {{ $screen['flow']['next']['title'] }}
@endif

@endif
---

@empty
화면 정보가 등록되지 않았습니다. 먼저 기획 단계(T16)에서 화면을 등록해 주세요.
@endforelse

## 3. 자주 묻는 질문 (FAQ)

### Q. 비밀번호를 잊어버렸어요

로그인 화면에서 **[비밀번호 찾기]** 를 클릭하세요.
등록된 이메일 주소로 재설정 링크가 발송됩니다.

### Q. 로그인이 안 돼요

1. 이메일과 비밀번호를 정확히 입력했는지 확인하세요.
2. 비밀번호를 5회 이상 잘못 입력하면 계정이 잠길 수 있습니다. 관리자에게 문의하세요.
3. 인터넷 연결 상태를 확인하세요.

### Q. 화면이 비정상적으로 표시돼요

1. 브라우저를 최신 버전으로 업데이트하세요.
2. 브라우저 캐시를 삭제하고 다시 시도해 보세요. (Ctrl+Shift+Delete)
3. 다른 브라우저로 접속해 보세요.

### Q. 데이터가 저장이 안 돼요

1. 필수 입력 항목이 모두 채워졌는지 확인하세요.
2. 입력 형식이 올바른지 확인하세요 (날짜, 숫자, 이메일 등).
3. 인터넷 연결이 끊어졌다면 다시 연결 후 시도하세요.

### Q. 특정 메뉴가 보이지 않아요

권한에 따라 표시되는 메뉴가 다를 수 있습니다.
필요한 기능 접근 권한이 없는 경우 시스템 관리자에게 권한 부여를 요청하세요.

---

## 4. 문제 해결

| 증상 | 가능한 원인 | 해결 방법 |
|------|------------|-----------|
| 페이지 로딩 안 됨 | 인터넷 연결 문제 | 네트워크 확인 후 새로고침(F5) |
| 로그인 실패 반복 | 잘못된 자격증명 또는 계정 잠금 | 비밀번호 찾기 또는 관리자 문의 |
| 데이터 저장 실패 | 유효성 오류 또는 중복 | 입력 내용 확인 후 재시도 |
| 파일 업로드 실패 | 파일 크기 초과 또는 지원 안 되는 형식 | 파일 크기/형식 확인 |
| 화면 깨짐 | 브라우저 호환성 문제 | Chrome/Edge 최신 버전 사용 |

---

## 5. 문의 및 지원

시스템 사용 중 해결되지 않는 문제가 발생하면 아래로 문의하세요:

- **시스템 관리자**: (연락처를 입력하세요)
- **기술 지원 이메일**: (이메일을 입력하세요)

---

_본 매뉴얼은 AI Agent (T50)에 의해 자동 생성되었습니다._
_내용 중 부정확한 부분이 있으면 시스템 관리자에게 알려주세요._
