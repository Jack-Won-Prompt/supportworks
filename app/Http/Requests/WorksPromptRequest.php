<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorksPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_input'                          => ['required', 'string', 'max:5000'],
            'project_id'                          => ['nullable', 'integer'],
            // 명확화 라운드 (프로젝트 모드 한정, 1라운드만)
            'session_id'                          => ['nullable', 'string', 'max:40'],
            'clarification_answers'               => ['nullable', 'array'],
            'clarification_answers.*.question_id' => ['required_with:clarification_answers', 'string', 'max:50'],
            'clarification_answers.*.answer'      => ['required_with:clarification_answers', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_input.required' => '질문 내용을 입력해주세요.',
            'user_input.max'      => '질문 내용은 5000자를 초과할 수 없습니다.',
            'project_id.integer'  => '프로젝트 식별자가 올바르지 않습니다.',
        ];
    }
}
