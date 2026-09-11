<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 대화 메시지의 번호(seq)를 여기 한 곳에서만 매긴다.
 *
 * 예전에는 서버와 데몬이 각자 번호를 매겼다. 지시문은 서버가 seq 0 으로 넣고
 * 데몬의 첫 답변도 자기 카운터의 0 이라, unique(job_id, seq) 에 걸린 답변을
 * insertOrIgnore 가 조용히 버렸다. 실제로 #19 의 537자 답변이 그렇게 사라졌다
 * — 데몬 로그 파일에는 남아 있는데 화면에는 지시문 하나뿐이었다.
 *
 * 번호는 서버가 배정하고, 데몬은 재전송 판별용 client_key 만 보낸다.
 * 두 출처가 같은 키를 두고 다투지 않게 하는 것이 요점이다.
 */
class MessageWriter
{
    /**
     * 메시지를 순서대로 덧붙인다. seq 는 이 안에서 배정하므로 넣지 않는다.
     *
     * @param  array<int, array<string, mixed>>  $rows  client_key 가 있으면 중복 저장을 건너뛴다
     * @return Collection<int, AiwJobMessage>  실제로 새로 만들어진 것만
     */
    public function append(AiwJob $job, array $rows): Collection
    {
        if ($rows === []) {
            return collect();
        }

        return DB::transaction(function () use ($job, $rows) {
            // 번호를 읽고 쓰는 사이 다른 요청이 끼어들면 같은 seq 가 두 번 나온다.
            // 대화형에서는 흔한 일이다 — 사람이 글을 보내는 동안 데몬도 답한다.
            // job 행을 잠가 대화 하나당 한 번에 하나씩만 번호를 매기게 한다.
            DB::table('aiw_jobs')->where('id', $job->id)->lockForUpdate()->first();

            $keys = array_values(array_filter(
                array_map(fn (array $r) => $r['client_key'] ?? null, $rows),
                fn ($k) => $k !== null && $k !== '',
            ));

            $seen = $keys === []
                ? collect()
                : AiwJobMessage::query()
                    ->where('job_id', $job->id)
                    ->whereIn('client_key', $keys)
                    ->pluck('client_key')
                    ->flip();

            $max = AiwJobMessage::where('job_id', $job->id)->max('seq');
            $next = $max === null ? 0 : ((int) $max) + 1;

            $created = collect();

            foreach ($rows as $row) {
                $key = $row['client_key'] ?? null;

                // 같은 요청을 다시 받은 것이다(네트워크 재시도). 번호를 낭비하지 않는다.
                if ($key !== null && $key !== '' && $seen->has($key)) {
                    continue;
                }

                $created->push(AiwJobMessage::create($row + [
                    'job_id'     => $job->id,
                    'seq'        => $next++,
                    'created_at' => $row['created_at'] ?? now(),
                ]));

                if ($key !== null && $key !== '') {
                    $seen->put($key, true);   // 한 배치 안의 중복도 걸러낸다
                }
            }

            return $created;
        });
    }

    /** 단건 편의 메서드. */
    public function appendOne(AiwJob $job, array $row): ?AiwJobMessage
    {
        return $this->append($job, [$row])->first();
    }
}
