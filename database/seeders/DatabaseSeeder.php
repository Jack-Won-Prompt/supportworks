<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMember;
use App\Models\Question;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 관리자
        $admin = User::create([
            'name' => '관리자',
            'email' => 'admin@supportworks.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'company' => 'SupportWorks',
        ]);

        // 내부 멤버
        $member1 = User::create([
            'name' => '김철수',
            'email' => 'member1@supportworks.com',
            'password' => Hash::make('password'),
            'role' => 'member',
            'company' => 'SupportWorks',
            'phone' => '010-1234-5678',
        ]);

        $member2 = User::create([
            'name' => '이영희',
            'email' => 'member2@supportworks.com',
            'password' => Hash::make('password'),
            'role' => 'member',
            'company' => 'SupportWorks',
        ]);

        // 외부 클라이언트
        $client1 = User::create([
            'name' => '박고객',
            'email' => 'client1@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'company' => 'ABC Corp',
            'phone' => '010-9999-0000',
        ]);

        $client2 = User::create([
            'name' => '정외부',
            'email' => 'client2@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'company' => 'XYZ Inc',
        ]);

        // 프로젝트 1
        $project1 = Project::create([
            'name' => '웹사이트 리뉴얼 프로젝트',
            'description' => '기존 웹사이트를 최신 디자인으로 리뉴얼하고 성능을 개선하는 프로젝트입니다.',
            'status' => 'active',
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
            'created_by' => $admin->id,
            'client_name' => 'ABC Corp',
            'client_email' => 'contact@abccorp.com',
        ]);

        ProjectMember::create(['project_id' => $project1->id, 'user_id' => $admin->id, 'role' => 'manager']);
        ProjectMember::create(['project_id' => $project1->id, 'user_id' => $member1->id, 'role' => 'member']);
        ProjectMember::create(['project_id' => $project1->id, 'user_id' => $member2->id, 'role' => 'member']);
        ProjectMember::create(['project_id' => $project1->id, 'user_id' => $client1->id, 'role' => 'viewer']);

        Schedule::create([
            'project_id' => $project1->id,
            'title' => '요구사항 분석',
            'description' => '클라이언트 요구사항을 수집하고 분석합니다.',
            'start_date' => '2026-04-01 09:00',
            'end_date' => '2026-04-05 18:00',
            'status' => 'completed',
            'priority' => 'high',
            'assigned_to' => $member1->id,
            'created_by' => $admin->id,
        ]);

        Schedule::create([
            'project_id' => $project1->id,
            'title' => 'UI/UX 디자인',
            'description' => '와이어프레임 및 디자인 시안 제작',
            'start_date' => '2026-04-08 09:00',
            'end_date' => '2026-04-20 18:00',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $member2->id,
            'created_by' => $admin->id,
        ]);

        Schedule::create([
            'project_id' => $project1->id,
            'title' => '프론트엔드 개발',
            'description' => 'React 기반 프론트엔드 구현',
            'start_date' => '2026-04-21 09:00',
            'end_date' => '2026-05-15 18:00',
            'status' => 'pending',
            'priority' => 'medium',
            'assigned_to' => $member1->id,
            'created_by' => $admin->id,
        ]);

        Schedule::create([
            'project_id' => $project1->id,
            'title' => '백엔드 API 개발',
            'start_date' => '2026-04-21 09:00',
            'end_date' => '2026-05-20 18:00',
            'status' => 'pending',
            'priority' => 'high',
            'assigned_to' => $member2->id,
            'created_by' => $admin->id,
        ]);

        Schedule::create([
            'project_id' => $project1->id,
            'title' => '테스트 및 배포',
            'start_date' => '2026-06-01 09:00',
            'end_date' => '2026-06-30 18:00',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $admin->id,
        ]);

        // Q&A
        $q1 = Question::create([
            'project_id' => $project1->id,
            'user_id' => $client1->id,
            'title' => '모바일 반응형 지원 범위는 어떻게 되나요?',
            'content' => '현재 모바일 사용자 비율이 60%를 넘습니다. iOS와 Android 최신 버전 지원이 필요하며, 태블릿도 지원되어야 합니다. 구체적인 지원 범위를 알려주세요.',
            'status' => 'answered',
        ]);

        $a1 = Answer::create([
            'question_id' => $q1->id,
            'user_id' => $member1->id,
            'content' => '안녕하세요. 모바일 반응형 지원 범위를 안내드립니다.\n\n- iOS 15 이상 (iPhone, iPad)\n- Android 10 이상\n- 화면 크기: 320px ~ 2560px\n\n모든 주요 브라우저(Chrome, Safari, Firefox)에서 테스트를 완료할 예정입니다.',
            'is_accepted' => true,
        ]);

        $q2 = Question::create([
            'project_id' => $project1->id,
            'user_id' => $client1->id,
            'title' => '기존 데이터 마이그레이션은 어떻게 진행되나요?',
            'content' => '현재 운영 중인 사이트에 약 50,000개의 게시물과 회원 데이터가 있습니다. 이 데이터를 새 사이트로 이전할 때 다운타임 없이 가능한지 궁금합니다.',
            'status' => 'open',
        ]);

        // 프로젝트 2
        $project2 = Project::create([
            'name' => 'ERP 시스템 구축',
            'description' => '중소기업 맞춤형 ERP 시스템을 개발합니다. 재무, 인사, 물류 모듈 포함.',
            'status' => 'active',
            'start_date' => '2026-03-01',
            'end_date' => '2026-09-30',
            'created_by' => $member1->id,
            'client_name' => 'XYZ Inc',
        ]);

        ProjectMember::create(['project_id' => $project2->id, 'user_id' => $member1->id, 'role' => 'manager']);
        ProjectMember::create(['project_id' => $project2->id, 'user_id' => $admin->id, 'role' => 'member']);
        ProjectMember::create(['project_id' => $project2->id, 'user_id' => $client2->id, 'role' => 'viewer']);

        Schedule::create([
            'project_id' => $project2->id,
            'title' => '시스템 설계',
            'start_date' => '2026-03-01 09:00',
            'end_date' => '2026-03-31 18:00',
            'status' => 'completed',
            'priority' => 'high',
            'assigned_to' => $member1->id,
            'created_by' => $member1->id,
        ]);

        Schedule::create([
            'project_id' => $project2->id,
            'title' => '재무 모듈 개발',
            'start_date' => '2026-04-01 09:00',
            'end_date' => '2026-05-31 18:00',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $admin->id,
            'created_by' => $member1->id,
        ]);

        // 완료된 프로젝트
        $project3 = Project::create([
            'name' => '앱 프로토타입 개발',
            'description' => '초기 MVP 프로토타입 개발 완료.',
            'status' => 'completed',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'created_by' => $admin->id,
        ]);

        ProjectMember::create(['project_id' => $project3->id, 'user_id' => $admin->id, 'role' => 'manager']);
        ProjectMember::create(['project_id' => $project3->id, 'user_id' => $member2->id, 'role' => 'member']);
    }
}
