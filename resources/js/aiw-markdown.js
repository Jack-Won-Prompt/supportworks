// AI Works 마크다운 렌더.
//
// Claude 가 생성한 마크다운은 신뢰하지 않는다. marked 로 HTML 을 만든 뒤
// DOMPurify 로 스크립트·이벤트 핸들러·javascript: URL 을 제거하고 넣는다.
import { marked } from 'marked';
import DOMPurify from 'dompurify';

marked.setOptions({ breaks: true, gfm: true });

// 링크는 새 탭으로 열되 opener 를 넘기지 않는다.
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
    if (node.tagName === 'A') {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
    }
});

export function renderMarkdown(text) {
    if (!text) return '';

    return DOMPurify.sanitize(marked.parse(String(text)), {
        // 폼·임베드·스크립트 계열은 통째로 배제한다.
        FORBID_TAGS: ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'],
        FORBID_ATTR: ['style', 'onerror', 'onload', 'onclick'],
    });
}

window.aiwRenderMarkdown = renderMarkdown;
