<?php

namespace App\Services\Popbill;

use Illuminate\Support\Facades\Log;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillMessaging;

class MessageService extends PopbillBaseService
{
    private PopbillMessaging $api;

    public function __construct()
    {
        parent::__construct();
        if (!defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }
        $this->api = $this->newService();
    }

    protected function newService(): object
    {
        $svc = new PopbillMessaging($this->linkId, $this->secretKey);
        $svc->IsTest($this->isTest);
        $svc->IPRestrictOnOff($this->ipRestrictOnOff);
        $svc->UseStaticIP($this->useStaticIp);
        $svc->UseLocalTimeYN($this->useLocalTimeYn);
        return $svc;
    }

    public function sendXms(
        string $corpNum,
        string $sender,
        string $subject,
        string $content,
        array  $messages,
        ?string $reserveDt = null,
        ?string $userId = null,
        ?string $requestNum = null
    ): string {
        try {
            return $this->api->SendXMS($corpNum, $sender, $subject, $content, $messages, $reserveDt, false, $userId, null, $requestNum);
        } catch (PopbillException $e) {
            $this->handleException($e);
        }
    }

    public function send(string $to, string $content, ?string $receiverName = null): string
    {
        $toNum = preg_replace('/\D/', '', $to);

        if (config('popbill.sms_simulate', app()->isLocal())) {
            $receipt = 'SIM-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
            Log::info('[Popbill][SMS][시뮬레이션] 발송', [
                'to'      => $toNum,
                'name'    => $receiverName,
                'content' => $content,
                'receipt' => $receipt,
            ]);
            return $receipt;
        }

        $sender = $this->resolveRegisteredSenderNum();

        Log::info('[Popbill][SMS] 발송 요청', [
            'to'      => $toNum,
            'name'    => $receiverName,
            'sender'  => $sender,
            'content' => $content,
        ]);

        $receipt = $this->sendXms(
            corpNum:    $this->corpNum,
            sender:     $sender,
            subject:    '',
            content:    $content,
            messages:   [['rcv' => $toNum, 'rcvnm' => $receiverName ?? '', 'msg' => $content]],
            userId:     $this->userId,
        );

        Log::info('[Popbill][SMS] 발송 완료', ['to' => $toNum, 'receipt' => $receipt]);

        return $receipt;
    }

    private function resolveRegisteredSenderNum(): string
    {
        try {
            $list = $this->api->GetSenderNumberList($this->corpNum);

            if (empty($list)) {
                Log::warning('[Popbill][SMS] 등록된 발신번호 없음 — 팝빌 콘솔에서 발신번호를 등록하세요.');
                return $this->senderNum;
            }

            $configured = preg_replace('/\D/', '', $this->senderNum);

            foreach ($list as $item) {
                $num = preg_replace('/\D/', '', $item->number ?? '');
                if ($num === $configured && ($item->state ?? -1) === 1) {
                    return $item->number;
                }
            }

            foreach ($list as $item) {
                if (($item->state ?? -1) === 1) {
                    Log::warning('[Popbill][SMS] 설정 발신번호 미등록 → 대체', [
                        'configured' => $this->senderNum,
                        'used'       => $item->number,
                    ]);
                    return $item->number;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Popbill][SMS] 발신번호 목록 조회 실패', ['error' => $e->getMessage()]);
        }

        return $this->senderNum;
    }
}
