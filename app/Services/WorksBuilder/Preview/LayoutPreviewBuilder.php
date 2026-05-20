<?php

namespace App\Services\WorksBuilder\Preview;

use App\Models\WorksBuilder\LayoutPreview;
use App\Models\WorksBuilder\TaskOption;

/**
 * 옵션 입력 → 와이어프레임 SVG 생성 (담당자 시각화 전용).
 *
 * 명세 v11 §1.4: AI 프롬프트에 SVG는 포함하지 않는다. 본 SVG는 담당자 화면 확인용.
 *
 * 구조: 슬롯 그리드 + 자기완결 부품(SlotRenderer)
 *  1. 옵션값으로부터 5개 슬롯(GNB / 탭 / 콘텐츠)의 직사각형을 먼저 결정
 *  2. 각 슬롯 사이에 8px 간격을 둠 → 영역이 절대 맞닿지 않음
 *  3. 각 부품은 자기 박스 (x,y,w,h) 안에서만 그림 — 박스가 너무 작으면 라벨만 표시
 */
class LayoutPreviewBuilder
{
    private const W   = 800;
    private const H   = 500;
    private const PAD = 14;   // 외곽 카드 안쪽 패딩
    private const GAP = 8;    // 슬롯 사이 간격

    // GNB 크기
    private const GNB_TOP_H   = 56;
    private const GNB_SIDE_W  = 120;

    // 탭 크기
    private const TAB_TOP_H   = 44;
    private const TAB_SIDE_W  = 140;

    // 팔레트
    private const C_BG    = '#f8fafc';
    private const C_LINE  = '#e2e8f0';
    private const C_MUTED = '#cbd5e1';
    private const C_TEXT  = '#94a3b8';
    private const C_DEEP  = '#475569';
    private const C_PANEL = '#ffffff';
    private const C_SURF2 = '#f1f5f9';

    public function build(TaskOption|array $source): string
    {
        $data       = $source instanceof TaskOption ? ($source->options_data ?? []) : $source;
        $gnb        = $data['gnb_position']    ?? 'top';
        $tab        = $data['tab_structure']   ?? 'single';
        $transition = $data['transition_type'] ?? 'page';
        $accent     = $this->safeColor($data['main_color'] ?? '#3b82f6');
        $accentSoft = $this->softenAccent($accent);

        $slots = $this->computeSlots($gnb, $tab);

        $parts = [];
        $parts[] = $this->defs();
        $parts[] = $this->background();

        if ($slots['gnb']) {
            $parts[] = $this->renderGnbSlot($slots['gnb'], $gnb, $accent);
        }
        if ($slots['tab']) {
            $parts[] = $this->renderTabSlot($slots['tab'], $tab, $accent, $accentSoft);
        }
        $parts[] = $this->renderContentSlot($slots['content'], $transition, $accent, $accentSoft);

        $parts[] = $this->modeBadge($transition, $accent);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" preserveAspectRatio="xMidYMid meet" font-family="-apple-system, BlinkMacSystemFont, sans-serif">%s</svg>',
            self::W, self::H, implode('', $parts),
        );
    }

    public function snapshot(TaskOption $options, string $svg): LayoutPreview
    {
        return LayoutPreview::create([
            'task_id'          => $options->task_id,
            'task_options_id'  => $options->id,
            'options_snapshot' => $options->options_data,
            'preview_svg'      => $svg,
            'preview_metadata' => ['w' => self::W, 'h' => self::H, 'version' => $options->version],
        ]);
    }

    /* ───────────────────────── 슬롯 계산 ───────────────────────── */

    /**
     * 5개 슬롯의 직사각형을 결정.
     * @return array{gnb: ?array, tab: ?array, content: array}
     */
    private function computeSlots(string $gnb, string $tab): array
    {
        // 외곽 카드(흰 패널) 안쪽 사용 가능 영역
        $box = [
            'x' => self::PAD,
            'y' => self::PAD,
            'w' => self::W - self::PAD * 2,
            'h' => self::H - self::PAD * 2,
        ];

        // 1) GNB 영역 분리
        $gnbBox = null;
        $afterGnb = $box;
        switch ($gnb) {
            case 'top':
                $gnbBox = ['x' => $box['x'], 'y' => $box['y'], 'w' => $box['w'], 'h' => self::GNB_TOP_H];
                $afterGnb = [
                    'x' => $box['x'],
                    'y' => $box['y'] + self::GNB_TOP_H + self::GAP,
                    'w' => $box['w'],
                    'h' => $box['h'] - self::GNB_TOP_H - self::GAP,
                ];
                break;
            case 'left':
                $gnbBox = ['x' => $box['x'], 'y' => $box['y'], 'w' => self::GNB_SIDE_W, 'h' => $box['h']];
                $afterGnb = [
                    'x' => $box['x'] + self::GNB_SIDE_W + self::GAP,
                    'y' => $box['y'],
                    'w' => $box['w'] - self::GNB_SIDE_W - self::GAP,
                    'h' => $box['h'],
                ];
                break;
            case 'right':
                $gnbBox = [
                    'x' => $box['x'] + $box['w'] - self::GNB_SIDE_W,
                    'y' => $box['y'],
                    'w' => self::GNB_SIDE_W,
                    'h' => $box['h'],
                ];
                $afterGnb = [
                    'x' => $box['x'],
                    'y' => $box['y'],
                    'w' => $box['w'] - self::GNB_SIDE_W - self::GAP,
                    'h' => $box['h'],
                ];
                break;
            case 'none':
            default:
                // afterGnb = box
                break;
        }

        // 2) 탭 영역 분리 (afterGnb 안에서)
        $tabBox = null;
        $content = $afterGnb;
        switch ($tab) {
            case 'top_tabs':
                $tabBox = [
                    'x' => $afterGnb['x'],
                    'y' => $afterGnb['y'],
                    'w' => $afterGnb['w'],
                    'h' => self::TAB_TOP_H,
                ];
                $content = [
                    'x' => $afterGnb['x'],
                    'y' => $afterGnb['y'] + self::TAB_TOP_H + self::GAP,
                    'w' => $afterGnb['w'],
                    'h' => $afterGnb['h'] - self::TAB_TOP_H - self::GAP,
                ];
                break;
            case 'left_tabs':
            case 'sidebar_tabs':
                $tabBox = [
                    'x' => $afterGnb['x'],
                    'y' => $afterGnb['y'],
                    'w' => self::TAB_SIDE_W,
                    'h' => $afterGnb['h'],
                ];
                $content = [
                    'x' => $afterGnb['x'] + self::TAB_SIDE_W + self::GAP,
                    'y' => $afterGnb['y'],
                    'w' => $afterGnb['w'] - self::TAB_SIDE_W - self::GAP,
                    'h' => $afterGnb['h'],
                ];
                break;
            case 'single':
            case 'none':
            default:
                // content = afterGnb
                break;
        }

        return ['gnb' => $gnbBox, 'tab' => $tabBox, 'content' => $content];
    }

    /* ───────────────────────── GNB 슬롯 ───────────────────────── */

    private function renderGnbSlot(array $box, string $gnb, string $accent): string
    {
        // 슬롯 카드 — accent 색상 배경
        $card = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="10" fill="%s" filter="url(#wb-shadow)"/>',
            $box['x'], $box['y'], $box['w'], $box['h'], $accent,
        );

        $content = $gnb === 'top'
            ? $this->drawTopGnb($box)
            : $this->drawSideGnb($box);

        // 슬롯 라벨 (작게)
        $label = $this->slotLabel($box, 'GNB · '.$this->gnbLabel($gnb), '#ffffff', 'rgba(255,255,255,0.18)');

        return $card.$content.$label;
    }

    private function drawTopGnb(array $box): string
    {
        if ($box['w'] < 200 || $box['h'] < 30) return '';

        $cy = $box['y'] + (int)($box['h'] / 2);
        $parts = '';

        // 로고
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="10" fill="rgba(255,255,255,0.95)"/>',
            $box['x'] + 24, $cy,
        );
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="60" height="6" rx="2" fill="rgba(255,255,255,0.9)"/>',
            $box['x'] + 42, $cy - 3,
        );

        // 검색바 (중앙)
        $sbW = min(260, $box['w'] - 240);
        if ($sbW >= 80) {
            $sbX = $box['x'] + (int)(($box['w'] - $sbW) / 2);
            $sbH = 22;
            $sbY = $cy - (int)($sbH / 2);
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="11" fill="rgba(255,255,255,0.18)" stroke="rgba(255,255,255,0.35)"/>',
                $sbX, $sbY, $sbW, $sbH,
            );
            $parts .= sprintf(
                '<circle cx="%d" cy="%d" r="4" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="1.5"/>',
                $sbX + 12, $cy,
            );
        }

        // 우측 아이콘 + 아바타
        $rx = $box['x'] + $box['w'] - 60;
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="9" fill="rgba(255,255,255,0.15)"/>',
            $rx, $cy,
        );
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="2.5" fill="#ef4444"/>',
            $rx + 5, $cy - 5,
        );
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="13" fill="rgba(255,255,255,0.92)"/>',
            $box['x'] + $box['w'] - 26, $cy,
        );

        return $parts;
    }

    private function drawSideGnb(array $box): string
    {
        if ($box['w'] < 60 || $box['h'] < 100) return '';

        $cx = $box['x'] + (int)($box['w'] / 2);
        $parts = '';

        // 로고 박스
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="44" height="44" rx="10" fill="rgba(255,255,255,0.95)"/>',
            $cx - 22, $box['y'] + 18,
        );

        // 메뉴 항목 (최대 5개)
        $startY = $box['y'] + 80;
        $itemH = 32;
        $maxItems = (int)(($box['h'] - 80 - 64) / ($itemH + 6));
        $maxItems = max(1, min(6, $maxItems));

        for ($i = 0; $i < $maxItems; $i++) {
            $isActive = $i === 0;
            $itemY = $startY + $i * ($itemH + 6);

            if ($isActive) {
                $parts .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" rx="6" fill="rgba(255,255,255,0.22)"/>',
                    $box['x'] + 10, $itemY, $box['w'] - 20, $itemH,
                );
            }
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="14" height="14" rx="3" fill="rgba(255,255,255,%s)"/>',
                $box['x'] + 18, $itemY + 9, $isActive ? '0.95' : '0.55',
            );
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="5" rx="1.5" fill="rgba(255,255,255,%s)"/>',
                $box['x'] + 38, $itemY + 13, max(20, $box['w'] - 54), $isActive ? '0.9' : '0.5',
            );
        }

        // 하단 사용자 아바타
        $userY = $box['y'] + $box['h'] - 40;
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="10" fill="rgba(255,255,255,0.85)"/>',
            $cx, $userY,
        );

        return $parts;
    }

    /* ───────────────────────── 탭 슬롯 ───────────────────────── */

    private function renderTabSlot(array $box, string $tab, string $accent, string $accentSoft): string
    {
        // 슬롯 카드
        $card = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="8" fill="%s" stroke="%s"/>',
            $box['x'], $box['y'], $box['w'], $box['h'], self::C_PANEL, self::C_LINE,
        );

        $content = ($tab === 'top_tabs')
            ? $this->drawTopTabs($box, $accent)
            : $this->drawSideTabs($box, $tab, $accent, $accentSoft);

        $label = $this->slotLabel($box, '탭 · '.$this->tabLabel($tab), self::C_DEEP, self::C_SURF2);

        return $card.$content.$label;
    }

    private function drawTopTabs(array $box, string $accent): string
    {
        if ($box['w'] < 120 || $box['h'] < 30) return '';

        $cy = $box['y'] + (int)($box['h'] / 2);
        $labels = [70, 60, 55, 65, 50];
        $px = $box['x'] + 16;
        $parts = '';

        foreach ($labels as $i => $labelW) {
            if ($px + $labelW + 20 > $box['x'] + $box['w'] - 8) break;
            $isActive = $i === 0;
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="6" rx="1.5" fill="%s"/>',
                $px, $cy - 3, $labelW, $isActive ? $accent : self::C_TEXT,
            );
            if ($isActive) {
                $parts .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="3" rx="1.5" fill="%s"/>',
                    $px - 4, $box['y'] + $box['h'] - 4, $labelW + 8, $accent,
                );
            }
            $px += $labelW + 24;
        }
        return $parts;
    }

    private function drawSideTabs(array $box, string $tab, string $accent, string $accentSoft): string
    {
        if ($box['w'] < 70 || $box['h'] < 100) return '';

        $startY = $box['y'] + 20;
        $itemH = 34;
        $maxItems = max(2, min(6, (int)(($box['h'] - 40) / ($itemH + 4))));
        $parts = '';

        for ($i = 0; $i < $maxItems; $i++) {
            $itemY = $startY + $i * ($itemH + 4);
            if ($itemY + $itemH > $box['y'] + $box['h'] - 16) break;
            $isActive = $i === 0;

            if ($isActive) {
                $parts .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" rx="6" fill="%s"/>',
                    $box['x'] + 8, $itemY, $box['w'] - 16, $itemH, $accentSoft,
                );
                $parts .= sprintf(
                    '<rect x="%d" y="%d" width="3" height="%d" rx="1.5" fill="%s"/>',
                    $box['x'] + 8, $itemY, $itemH, $accent,
                );
            }
            $parts .= sprintf(
                '<circle cx="%d" cy="%d" r="3" fill="%s"/>',
                $box['x'] + 22, $itemY + (int)($itemH / 2),
                $isActive ? $accent : self::C_TEXT,
            );
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="5" rx="1.5" fill="%s"/>',
                $box['x'] + 32, $itemY + (int)($itemH / 2) - 2,
                max(20, $box['w'] - 48), $isActive ? $accent : self::C_TEXT,
            );
        }

        // sidebar_tabs면 상단에 사이드바 헤더 라벨 추가
        if ($tab === 'sidebar_tabs') {
            // (이미 startY를 20부터 시작했으므로 별도 처리 없음 — 라벨만 변경됨)
        }

        return $parts;
    }

    /* ───────────────────────── 콘텐츠 슬롯 ───────────────────────── */

    private function renderContentSlot(array $box, string $transition, string $accent, string $accentSoft): string
    {
        // 콘텐츠 슬롯 카드 (배경)
        $card = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="8" fill="%s" stroke="%s"/>',
            $box['x'], $box['y'], $box['w'], $box['h'], self::C_PANEL, self::C_LINE,
        );

        // 박스가 너무 작으면 placeholder만
        if ($box['w'] < 180 || $box['h'] < 120) {
            $small = $this->drawPlaceholder($box, $transition, $accent);
            $label = $this->slotLabel($box, '콘텐츠', self::C_DEEP, self::C_SURF2);
            return $card.$small.$label;
        }

        $inner = match ($transition) {
            'slide'      => $this->drawContentSlide($box, $accent, $accentSoft),
            'tab_switch' => $this->drawContentTabSwitch($box, $accent, $accentSoft),
            'page'       => $this->drawContentPage($box, $accent, $accentSoft),
            default      => $this->drawContentPage($box, $accent, $accentSoft),
        };

        $label = $this->slotLabel($box, '콘텐츠 · '.$this->transitionLabel($transition), self::C_DEEP, self::C_SURF2);

        return $card.$inner.$label;
    }

    /** 페이지 메타포: 헤더 + KPI + 하단 영역 */
    private function drawContentPage(array $box, string $accent, string $accentSoft): string
    {
        $pad = 18;
        $cx = $box['x'] + $pad;
        $cy = $box['y'] + $pad + 16; // 슬롯 라벨 자리 띄움
        $cw = $box['w'] - $pad * 2;
        $ch = $box['h'] - $pad * 2 - 16;

        $parts = '';

        // 페이지 헤더 라인
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="11" rx="2.5" fill="%s"/>',
            $cx, $cy, min(160, (int)($cw * 0.4)), self::C_DEEP,
        );
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="5" rx="1.5" fill="%s"/>',
            $cx, $cy + 19, min(120, (int)($cw * 0.3)), self::C_MUTED,
        );

        // 우측 CTA
        if ($cw >= 280) {
            $btnW = 80; $btnH = 24;
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="5" fill="%s"/>',
                $cx + $cw - $btnW, $cy - 4, $btnW, $btnH, $accent,
            );
        }

        // KPI 카드 행
        $bodyY = $cy + 40;
        $bodyH = $ch - 40;
        if ($bodyH < 60) return $parts;

        $kpiH = 64;
        $kpiCount = $cw >= 460 ? 3 : ($cw >= 320 ? 2 : 1);
        $gap = 12;
        $kpiW = (int) (($cw - $gap * ($kpiCount - 1)) / $kpiCount);

        for ($i = 0; $i < $kpiCount; $i++) {
            $kx = $cx + $i * ($kpiW + $gap);
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="6" fill="%s" stroke="%s"/>',
                $kx, $bodyY, $kpiW, $kpiH, self::C_SURF2, self::C_LINE,
            );
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="50" height="5" rx="1.5" fill="%s"/>',
                $kx + 12, $bodyY + 12, self::C_TEXT,
            );
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="10" rx="2" fill="%s"/>',
                $kx + 12, $bodyY + 26, max(30, (int)($kpiW * 0.45)), $accent,
            );
            // 미니 스파크라인
            $parts .= sprintf(
                '<polyline points="%d,%d %d,%d %d,%d %d,%d" fill="none" stroke="%s" stroke-width="1.5" opacity="0.7"/>',
                $kx + 12,            $bodyY + $kpiH - 12,
                $kx + 12 + 14,       $bodyY + $kpiH - 18,
                $kx + 12 + 28,       $bodyY + $kpiH - 14,
                $kx + 12 + 42,       $bodyY + $kpiH - 22,
                $accent,
            );
        }

        // 하단 큰 영역 (차트 or 리스트)
        $lowerY = $bodyY + $kpiH + 14;
        $lowerH = $bodyY + $bodyH - $lowerY;
        if ($lowerH >= 60) {
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="6" fill="%s" stroke="%s"/>',
                $cx, $lowerY, $cw, $lowerH, self::C_SURF2, self::C_LINE,
            );
            // 미니 막대그래프
            $bars = 6;
            $bw = (int)(($cw - 40) / ($bars * 1.6));
            $heights = [0.4, 0.7, 0.55, 0.8, 0.6, 0.9];
            for ($i = 0; $i < $bars; $i++) {
                $bx = $cx + 20 + $i * (int)(($cw - 40) / $bars);
                $bh = (int)($lowerH * 0.6 * $heights[$i]);
                $by = $lowerY + $lowerH - 14 - $bh;
                $parts .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" rx="2" fill="%s"/>',
                    $bx, $by, $bw, $bh, $i === 5 ? $accent : $accentSoft,
                );
            }
        }

        return $parts;
    }

    /** 슬라이드 메타포: 좌/우 화살표 + 가운데 캐러셀 카드 + 인디케이터 */
    private function drawContentSlide(array $box, string $accent, string $accentSoft): string
    {
        $pad = 18;
        $cx = $box['x'] + $pad;
        $cy = $box['y'] + $pad + 16;
        $cw = $box['w'] - $pad * 2;
        $ch = $box['h'] - $pad * 2 - 16;

        $parts = '';
        // 헤더 라인
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="11" rx="2.5" fill="%s"/>',
            $cx, $cy, min(160, (int)($cw * 0.4)), self::C_DEEP,
        );

        $bodyY = $cy + 28;
        $bodyH = $ch - 28 - 16; // 하단 인디케이터 자리

        $arrowR = 18;
        $arrowCy = $bodyY + (int)($bodyH / 2);

        // 좌 화살표
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="%d" fill="%s" stroke="%s"/>',
            $cx + $arrowR, $arrowCy, $arrowR, self::C_PANEL, self::C_LINE,
        );
        $parts .= sprintf(
            '<polyline points="%d,%d %d,%d %d,%d" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            $cx + $arrowR + 3, $arrowCy - 6,
            $cx + $arrowR - 3, $arrowCy,
            $cx + $arrowR + 3, $arrowCy + 6,
            $accent,
        );
        // 우 화살표
        $parts .= sprintf(
            '<circle cx="%d" cy="%d" r="%d" fill="%s" stroke="%s"/>',
            $cx + $cw - $arrowR, $arrowCy, $arrowR, self::C_PANEL, self::C_LINE,
        );
        $parts .= sprintf(
            '<polyline points="%d,%d %d,%d %d,%d" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            $cx + $cw - $arrowR - 3, $arrowCy - 6,
            $cx + $cw - $arrowR + 3, $arrowCy,
            $cx + $cw - $arrowR - 3, $arrowCy + 6,
            $accent,
        );

        // 가운데 캐러셀 카드
        $cardX = $cx + ($arrowR * 2 + 12);
        $cardW = $cw - ($arrowR * 2 + 12) * 2;
        if ($cardW > 80 && $bodyH > 60) {
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="8" fill="%s" stroke="%s"/>',
                $cardX, $bodyY, $cardW, $bodyH, self::C_SURF2, self::C_LINE,
            );
            // 이미지 영역
            $imgH = (int)($bodyH * 0.55);
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="4" fill="%s"/>',
                $cardX + 14, $bodyY + 14, $cardW - 28, $imgH, $accentSoft,
            );
            // 텍스트 라인
            $tx = $cardX + 14;
            $ty = $bodyY + 14 + $imgH + 14;
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="7" rx="2" fill="%s"/>',
                $tx, $ty, (int)(($cardW - 28) * 0.55), self::C_DEEP,
            );
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="4" rx="1" fill="%s"/>',
                $tx, $ty + 13, (int)(($cardW - 28) * 0.8), self::C_MUTED,
            );
        }

        // 하단 인디케이터
        $iy = $box['y'] + $box['h'] - $pad - 6;
        $ix = $cx + (int)($cw / 2) - 36;
        for ($i = 0; $i < 5; $i++) {
            $isActive = $i === 2;
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="5" rx="2.5" fill="%s"/>',
                $ix + $i * 16, $iy, $isActive ? 18 : 6, $isActive ? $accent : self::C_MUTED,
            );
        }

        return $parts;
    }

    /** 탭 스위치 메타포: 알약 탭 + 활성 콘텐츠 테이블 */
    private function drawContentTabSwitch(array $box, string $accent, string $accentSoft): string
    {
        $pad = 18;
        $cx = $box['x'] + $pad;
        $cy = $box['y'] + $pad + 16;
        $cw = $box['w'] - $pad * 2;
        $ch = $box['h'] - $pad * 2 - 16;

        $parts = '';
        // 헤더
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="11" rx="2.5" fill="%s"/>',
            $cx, $cy, min(140, (int)($cw * 0.35)), self::C_DEEP,
        );

        // 알약 탭 4개
        $pillY = $cy + 26;
        $px = $cx;
        $pw = 60;
        $pillCount = min(4, (int)($cw / ($pw + 10)));
        for ($i = 0; $i < $pillCount; $i++) {
            $isActive = $i === 0;
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="22" rx="11" fill="%s" stroke="%s"/>',
                $px, $pillY, $pw,
                $isActive ? $accent : self::C_PANEL,
                $isActive ? $accent : self::C_LINE,
            );
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="32" height="5" rx="1.5" fill="%s"/>',
                $px + 14, $pillY + 9,
                $isActive ? 'rgba(255,255,255,0.95)' : self::C_TEXT,
            );
            $px += $pw + 8;
        }

        // 활성 탭 콘텐츠 카드 (테이블 메타포)
        $bodyY = $pillY + 32;
        $bodyH = $cy + $ch - $bodyY;
        if ($bodyH < 60) return $parts;

        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="6" fill="%s" stroke="%s"/>',
            $cx, $bodyY, $cw, $bodyH, self::C_PANEL, self::C_LINE,
        );
        // 테이블 헤더
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="26" rx="6" fill="%s"/>',
            $cx, $bodyY, $cw, $accentSoft,
        );
        $parts .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="16" fill="%s"/>',
            $cx, $bodyY + 10, $cw, $accentSoft,
        );
        // 헤더 라벨
        $cols = 4;
        for ($i = 0; $i < $cols; $i++) {
            $colX = $cx + 14 + $i * (int)(($cw - 28) / $cols);
            $parts .= sprintf(
                '<rect x="%d" y="%d" width="46" height="5" rx="1.5" fill="%s"/>',
                $colX, $bodyY + 11, self::C_TEXT,
            );
        }
        // 행
        $rowH = 28;
        $maxRows = max(2, min(5, (int)(($bodyH - 30) / $rowH)));
        for ($r = 0; $r < $maxRows; $r++) {
            $ry = $bodyY + 30 + $r * $rowH;
            if ($ry + $rowH > $bodyY + $bodyH - 4) break;
            $parts .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s"/>',
                $cx + 6, $ry, $cx + $cw - 6, $ry, self::C_LINE,
            );
            for ($i = 0; $i < $cols; $i++) {
                $colX = $cx + 14 + $i * (int)(($cw - 28) / $cols);
                $widths = [40, 70, 35, 50];
                $parts .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="5" rx="1.5" fill="%s"/>',
                    $colX, $ry + 11, $widths[$i] ?? 40,
                    $i === 0 ? self::C_DEEP : self::C_MUTED,
                );
            }
        }

        return $parts;
    }

    private function drawPlaceholder(array $box, string $transition, string $accent): string
    {
        $cx = $box['x'] + (int)($box['w'] / 2);
        $cy = $box['y'] + (int)($box['h'] / 2);
        return sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="6" fill="%s" stroke="%s" stroke-dasharray="4 4"/>'
            .'<circle cx="%d" cy="%d" r="4" fill="%s"/>',
            $box['x'] + 6, $box['y'] + 6, $box['w'] - 12, $box['h'] - 12, self::C_SURF2, self::C_MUTED,
            $cx, $cy, $accent,
        );
    }

    /* ───────────────────────── 슬롯 라벨 ───────────────────────── */

    private function slotLabel(array $box, string $text, string $color, string $bgColor): string
    {
        if ($box['w'] < 100 || $box['h'] < 24) return '';
        // 좌상단 작은 라벨
        $w = strlen($text) * 5 + 16;
        return sprintf(
            '<g><rect x="%d" y="%d" width="%d" height="14" rx="7" fill="%s"/>'
            .'<text x="%d" y="%d" font-size="9" font-weight="600" fill="%s">%s</text></g>',
            $box['x'] + 8, $box['y'] + 8, $w, $bgColor,
            $box['x'] + 16, $box['y'] + 18, $color, $this->escapeXml($text),
        );
    }

    /* ───────────────────────── 공통 ───────────────────────── */

    private function defs(): string
    {
        return '<defs><filter id="wb-shadow" x="-5%" y="-5%" width="110%" height="115%">'
            . '<feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#0f172a" flood-opacity="0.10"/>'
            . '</filter></defs>';
    }

    private function background(): string
    {
        return sprintf(
            '<rect x="0" y="0" width="%d" height="%d" fill="%s"/>',
            self::W, self::H, self::C_BG,
        );
    }

    private function modeBadge(string $transition, string $accent): string
    {
        $label = $this->transitionLabel($transition);
        $w = strlen($label) * 6 + 24;
        $x = self::W - 14 - $w - 4;
        $y = -2; // 화면 밖으로 노출되지 않게 SVG 안에서 4px 띄움
        $y = 4;

        return sprintf(
            '<g filter="url(#wb-shadow)"><rect x="%d" y="%d" width="%d" height="20" rx="10" fill="rgba(255,255,255,0.97)" stroke="%s"/>'
            .'<circle cx="%d" cy="%d" r="3" fill="%s"/>'
            .'<text x="%d" y="%d" font-size="10" font-weight="700" fill="%s">%s</text></g>',
            $x, $y, $w, $accent,
            $x + 12, $y + 10, $accent,
            $x + 20, $y + 14, $accent, $label,
        );
    }

    private function gnbLabel(string $gnb): string
    {
        return match ($gnb) {
            'top'   => '상단',
            'left'  => '좌측',
            'right' => '우측',
            default => '없음',
        };
    }

    private function tabLabel(string $tab): string
    {
        return match ($tab) {
            'top_tabs'     => '상단',
            'left_tabs'    => '좌측',
            'sidebar_tabs' => '사이드',
            'single'       => '단일',
            default        => '없음',
        };
    }

    private function transitionLabel(string $transition): string
    {
        return match ($transition) {
            'slide'      => 'Slide',
            'tab_switch' => 'Tab Switch',
            'page'       => 'Page',
            default      => $transition,
        };
    }

    private function safeColor(?string $color): string
    {
        if (is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }
        return '#3b82f6';
    }

    private function softenAccent(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return sprintf('rgba(%d,%d,%d,0.16)', $r, $g, $b);
    }

    private function escapeXml(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
