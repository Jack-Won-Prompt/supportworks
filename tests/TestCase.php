<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * 안전장치: 테스트는 개발/운영 DB 를 절대 건드리면 안 된다.
     *
     * RefreshDatabase 는 대상 DB 의 테이블을 전부 지우고 마이그레이션을 다시 돌린다.
     * phpunit.xml 의 DB_DATABASE 설정이 어떤 이유로든 먹지 않으면 개발 DB(supportworks)가
     * 통째로 날아간다. 이름이 `_test` 로 끝나지 않으면 여기서 즉시 중단한다.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = config('database.connections.'.config('database.default').'.database');

        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "테스트가 비-테스트 DB 를 가리키고 있습니다: [{$database}]. "
                ."phpunit.xml 의 DB_DATABASE 를 확인하세요. 데이터 보호를 위해 중단합니다."
            );
        }
    }
}
