@extends('layouts.admin')

@section('title', __('admin.user_create_title'))

@section('content')
<div class="max-w-lg pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_email') }} <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.password_label') }} <span class="text-red-500">*</span></label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.password_confirm_label') }} <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-3 gap-4 items-end">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_role') }} <span class="text-red-500">*</span></label>
                    <select name="role" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="client">{{ __('admin.role_client') }}</option>
                        <option value="member">{{ __('admin.role_member') }}</option>
                        <option value="manager">{{ __('admin.role_manager') }}</option>
                        <option value="admin">{{ __('admin.role_admin') }}</option>
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-50 hover:bg-gray-100 cursor-pointer">
                    <input type="checkbox" name="is_sr_agent" value="1" {{ old('is_sr_agent') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <span class="font-medium text-gray-700">SR 담당자</span>
                </label>
                <label class="inline-flex items-center gap-2 px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-50 hover:bg-gray-100 cursor-pointer">
                    <input type="checkbox" name="is_aiw_operator" value="1" {{ old('is_aiw_operator') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <span class="font-medium text-gray-700" title="켜면 본인이 구성원인 프로젝트에서 작업 지시를 쓸 수 있습니다. 지시 한 줄이 작업 PC 의 소스를 고치고 배포까지 하므로, 맡길 사람에게만 켜 주세요.">작업 지시 가능</span>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_company') }}</label>
                    <input type="text" name="company" value="{{ old('company') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.usr_contact') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.usr_affiliated_group') }}</label>
                <select name="company_group_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('admin.unassigned') }}</option>
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ old('company_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('admin.usr_create_btn') }}</button>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('admin.usr_cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
