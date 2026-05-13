<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromptRefineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id'                          => ['nullable', 'string', 'max:40'],
            'user_input'                          => ['required', 'string', 'max:5000'],
            'mode'                                => ['required', 'in:general,project'],
            'project_id'                          => ['nullable', 'integer', 'required_if:mode,project'],
            'schedule_id'                         => ['nullable', 'integer'],
            'clarification_answers'               => ['nullable', 'array'],
            'clarification_answers.*.question_id' => ['required', 'string'],
            'clarification_answers.*.answer'      => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_input.required'       => '입력 내용을 작성해주세요.',
            'user_input.max'            => '입력 내용은 5000자를 초과할 수 없습니다.',
            'mode.required'             => '모드를 선택해주세요.',
            'mode.in'                   => '모드는 general 또는 project이어야 합니다.',
            'project_id.required_if'    => '프로젝트 모드에서는 프로젝트를 선택해주세요.',
        ];
    }
}
