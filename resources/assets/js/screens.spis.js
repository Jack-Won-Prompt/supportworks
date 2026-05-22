/* 03.std — SPIS 고유 화면 등록
   표준 screens.registry.js 의 window.SPIS_SCREENS 를 확장한다.
   (표준 9개 화면은 그대로 두고 SPIS 화면을 추가) */
window.SPIS_SCREENS = Object.assign(window.SPIS_SCREENS || {}, {
  "cot050form": {
    label: "계약자등록",
    nav: "cot050form",
    group: "contract",
    css: [],
    js: []
  },
  "cus210form": {
    label: "SMS전송",
    nav: "cus210form",
    group: "customer",
    css: [],
    js: []
  },
  "lon020form": {
    label: "대납이자등록",
    nav: "lon020form",
    group: "payment",
    css: [],
    js: []
  },
  "lon040form": {
    label: "대납이자상환등록",
    nav: "lon040form",
    group: "payment",
    css: [],
    js: []
  },
  "cus070list": {
    label: "고객정보",
    nav: "cus070list",
    group: "customer",
    css: [],
    js: []
  },
  "cot480list": {
    label: "신고서일괄출력",
    nav: "cot480list",
    group: "contract",
    css: [],
    js: []
  },
  "cot410form": {
    label: "계약서관리",
    nav: "cot410form",
    group: "contract",
    css: [],
    js: []
  },
  "sit020form": {
    label: "기본정보등록",
    nav: "sit020form",
    group: "site",
    css: [],
    js: []
  },
  "sit120form": {
    label: "평형정보등록",
    nav: "sit120form",
    group: "site",
    css: [],
    js: []
  },
  "sit160form": {
    label: "동/호수등록",
    nav: "sit160form",
    group: "site",
    css: [],
    js: []
  },
  "sit190form": {
    label: "분양가변경등록",
    nav: "sit190form",
    group: "site",
    css: [],
    js: []
  },
  "sit210form": {
    label: "월마감",
    nav: "sit210form",
    group: "site",
    css: [],
    js: []
  },
  "sit280form": {
    label: "가상계좌 등록관리",
    nav: "sit280form",
    group: "site",
    css: [],
    js: []
  },
  "sit220list": {
    label: "동호수정보",
    nav: "sit220list",
    group: "site",
    css: [],
    js: []
  },
  "sit240list": {
    label: "업무보고(현장)",
    nav: "sit240list",
    group: "site",
    css: [],
    js: []
  },
  "sit290list": {
    label: "업무보고(옵션-타입별)",
    nav: "sit290list",
    group: "site",
    css: [],
    js: []
  },
  "sit260list": {
    label: "분양가변경리스트",
    nav: "sit260list",
    group: "site",
    css: [],
    js: []
  },
  "cot010form": {
    label: "청약자등록", nav: "cot010form", group: "contract",
    css: [],
    js: []
  },
  "cot100form": {
    label: "계약자변경등록", nav: "cot100form", group: "contract",
    css: [],
    js: []
  },
  "cot160form": {
    label: "옵션변경등록", nav: "cot160form", group: "contract",
    css: [],
    js: []
  },
  "cot200form": {
    label: "사건관리등록", nav: "cot200form", group: "contract",
    css: [],
    js: []
  },
  "cot430form": {
    label: "계약상태관리", nav: "cot430form", group: "contract",
    css: [],
    js: []
  },
  "cot230list": {
    label: "계약자현황", nav: "cot230list", group: "contract",
    css: [],
    js: []
  },
  "cot260list": {
    label: "호수별미분양현황", nav: "cot260list", group: "contract",
    css: [],
    js: []
  },
  "cot270list": { label: "계약자명의변경현황", nav: "cot270list", group: "contract", css: [], js: [] },
  "cot280list": { label: "옵션계약현황", nav: "cot280list", group: "contract", css: [], js: [] },
  "cot300list": { label: "사건관리현황", nav: "cot300list", group: "contract", css: [], js: [] },
  "cot310list": { label: "해약자LIST", nav: "cot310list", group: "contract", css: [], js: [] },
  "cot440list": { label: "계약자이력변경현황", nav: "cot440list", group: "contract", css: [], js: [] },
  "cot450list": { label: "고객상담내역", nav: "cot450list", group: "contract", css: [], js: [] },
  "cot460list": { label: "입주예정자현황", nav: "cot460list", group: "contract", css: [], js: [] },
  "cot470list": { label: "대량신고자료", nav: "cot470list", group: "contract", css: [], js: [] },
  "cot500list": { label: "계약서재발급내역", nav: "cot500list", group: "contract", css: [], js: [] },
  "cot510list": { label: "계약자 가상계좌", nav: "cot510list", group: "contract", css: [], js: [] },
  "cot600list": { label: "계약자 현황(분리보관)", nav: "cot600list", group: "contract", css: [], js: [] },
  "rcv010form": { label: "입금등록", nav: "rcv010form", group: "payment", css: [], js: [] },
  "rcv063form": { label: "입금일괄수정", nav: "rcv063form", group: "payment", css: [], js: [] },
  "rcv060form": { label: "입금수정", nav: "rcv060form", group: "payment", css: [], js: [] },
  "rcv390form": { label: "입금처리(신)", nav: "rcv390form", group: "payment", css: [], js: [] },
  "rcv410form": { label: "임시입금처리(신)", nav: "rcv410form", group: "payment", css: [], js: [] },
  "rcv090list": { label: "일일입금현황", nav: "rcv090list", group: "payment", css: [], js: [] },
  "rcv120list": { label: "월별입금집계표", nav: "rcv120list", group: "payment", css: [], js: [] },
  "rcv130list": { label: "세대별입금내역서", nav: "rcv130list", group: "payment", css: [], js: [] },
  "rcv150list": { label: "세대별입금집계표", nav: "rcv150list", group: "payment", css: [], js: [] },
  "rcv160list": { label: "약정도래내역서", nav: "rcv160list", group: "payment", css: [], js: [] },
  "rcv170list": { label: "차수별입금현황", nav: "rcv170list", group: "payment", css: [], js: [] },
  "rcv210list": { label: "초과입금내역서", nav: "rcv210list", group: "payment", css: [], js: [] },
  "rcv320list": { label: "부가세신고내역", nav: "rcv320list", group: "payment", css: [], js: [] },
  "rcv330list": { label: "세대별입금내역", nav: "rcv330list", group: "payment", css: [], js: [] },
  "rcv340list": { label: "조정금액내역서", nav: "rcv340list", group: "payment", css: [], js: [] },
  "rcv350list": { label: "완납세대LIST", nav: "rcv350list", group: "payment", css: [], js: [] },
  "rcv260list": { label: "세대별경과미수금", nav: "rcv260list", group: "payment", css: [], js: [] },
  "rcv270list": { label: "경과미수연체료내역", nav: "rcv270list", group: "payment", css: [], js: [] },
  "occ010form": { label: "입주기간등록", nav: "occ010form", group: "move-in", css: [], js: [] },
  "occ020form": { label: "선수금조정처리", nav: "occ020form", group: "move-in", css: [], js: [] },
  "occ060form": { label: "입주예정일등록", nav: "occ060form", group: "move-in", css: [], js: [] },
  "occ080form": { label: "입주정산처리", nav: "occ080form", group: "move-in", css: [], js: [] },
  "occ120form": { label: "초과입금환불처리", nav: "occ120form", group: "move-in", css: [], js: [] },
  "occ210form": { label: "선수금조정처리(신)", nav: "occ210form", group: "move-in", css: [], js: [] },
  "occ140list": { label: "선수금조정명세서", nav: "occ140list", group: "move-in", css: [], js: [] },
  "occ150list": { label: "입주예정일신청현황", nav: "occ150list", group: "move-in", css: [], js: [] },
  "occ160list": { label: "입주증발급현황", nav: "occ160list", group: "move-in", css: [], js: [] },
  "occ170list": { label: "세대별입주현황", nav: "occ170list", group: "move-in", css: [], js: [] },
  "vou010form": { label: "전표처리기준", nav: "vou010form", group: "voucher", css: [], js: [] },
  "vou040form": { label: "초과입금전표", nav: "vou040form", group: "voucher", css: [], js: [] },
  "vou050form": { label: "초과입금환불전표", nav: "vou050form", group: "voucher", css: [], js: [] },
  "vou060form": { label: "해약전표", nav: "vou060form", group: "voucher", css: [], js: [] },
  "vou110form": { label: "고객 등록", nav: "vou110form", group: "voucher", css: [], js: [] },
  "vou120form": { label: "입금전표(신)", nav: "vou120form", group: "voucher", css: [], js: [] },
  "vou130form": { label: "선수금전표(신)", nav: "vou130form", group: "voucher", css: [], js: [] },
  "cus010list": { label: "계약자검색", nav: "cus010list", group: "customer", css: [], js: [] },
  "cus020list": { label: "고객상담", nav: "cus020list", group: "customer", css: [], js: [] },
  "cus190list": { label: "안내장출력", nav: "cus190list", group: "customer", css: [], js: [] },
  "cus220list": { label: "스티커출력", nav: "cus220list", group: "customer", css: [], js: [] },
  "cus230list": { label: "납부확인서", nav: "cus230list", group: "customer", css: [], js: [] },
  "cus240list": { label: "분양관리대장", nav: "cus240list", group: "customer", css: [], js: [] },
  "cus280list": { label: "입주정산확인서", nav: "cus280list", group: "customer", css: [], js: [] },
  "cus290form": { label: "계약해지통보", nav: "cus290form", group: "customer", css: [], js: [] },
  "cus300form": { label: "계약해지등기번호", nav: "cus300form", group: "customer", css: [], js: [] },
  "cus400form": { label: "거래신고관리", nav: "cus400form", group: "customer", css: [], js: [] },
  "sts050list": { label: "년도별분양현황", nav: "sts050list", group: "report", css: [], js: [] },
  "sts060list": { label: "년도별입금현황", nav: "sts060list", group: "report", css: [], js: [] },
  "sts150list": { label: "SMS/MMS발송현황", nav: "sts150list", group: "report", css: [], js: [] },
  "ass100list": { label: "조합현황", nav: "ass100list", group: "union", css: [], js: [] },
  "ass010form": { label: "조합등록", nav: "ass010form", group: "union", css: [], js: [] },
  "ass040form": { label: "조합원약정등록", nav: "ass040form", group: "union", css: [], js: [] },
  "ass070form": { label: "신동/호수확정", nav: "ass070form", group: "union", css: [], js: [] },
  "ass110form": { label: "조합원추가부담금", nav: "ass110form", group: "union", css: [], js: [] },
  "ele010form": { label: "계약서", nav: "ele010form", group: "print", css: [], js: [] },
  "ele030form": { label: "부속서류", nav: "ele030form", group: "print", css: [], js: [] },
  "ele040form": { label: "입주증", nav: "ele040form", group: "print", css: [], js: [] },
  "ele050form": { label: "전자문서 현황", nav: "ele050form", group: "print", css: [], js: [] },
  "sys020form": { label: "휴일등록", nav: "sys020form", group: "system", css: [], js: [] },
  "sys030form": { label: "사용자별현장등록", nav: "sys030form", group: "system", css: [], js: [] },
  "sys050form": { label: "현장별사용자등록", nav: "sys050form", group: "system", css: [], js: [] },
  "sys060form": { label: "그룹별사용자등록", nav: "sys060form", group: "system", css: [], js: [] },
  "sys070form": { label: "그룹별메뉴등록", nav: "sys070form", group: "system", css: [], js: [] },
  "sys080form": { label: "은행등록", nav: "sys080form", group: "system", css: [], js: [] },
  "sys100form": { label: "접속 로그", nav: "sys100form", group: "system", css: [], js: [] },
  "sys110form": { label: "메뉴별 Action 로그", nav: "sys110form", group: "system", css: [], js: [] },
  "sys120form": { label: "계정(권한) 변경 로그", nav: "sys120form", group: "system", css: [], js: [] },
  "sys140form": { label: "부서이동자 권한점검", nav: "sys140form", group: "system", css: [], js: [] },
  "lon060list": { label: "대출현황", nav: "lon060list", group: "payment", css: [], js: [] },
  "lon090list": { label: "대출세대상환표", nav: "lon090list", group: "payment", css: [], js: [] }
});
