<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->name }}と{{ $company->name }}とのメッセージ
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if ($messages)
                    <x-flash-message status="session('status')" />
                    <x-auth-validation-errors class="mb-4" :errors="$errors" />
                    <h1 class="sm:text-3xl text-2xl font-medium text-center title-font text-gray-900 my-4">
                        {{ $company->name }}とのメッセージ
                    </h1>
                    <form method="POST" id="delete_{{ $user->id }}" action="{{ route('admin.users.messageDelete', ['company' => $company->id, 'user' => $user->id]) }}">
                        @csrf
                        @method('delete')
                        <ul class="list">
                            @foreach ($messages as $message)
                            <li>
                                @if ($message->sent_from === 1)
                                <div class="mx-2 my-6 flex flex-col">
                                    <input type=checkbox name="messages[]" class="ml-auto" value="{{ $message->id }}">
                                    <p class="ml-auto
                                                    flex p-2 rounded bg-indigo-200 box-border lg:max-w-2xl md:max-w-lg
                                                    max-w-md">
                                        {!! $message->body !!}
                                    </p>
                                    <div class="ml-auto">
                                        <small>{{ date('Y-m-d H:i', strtotime($message->sent_time)) }}</small>
                                    </div>
                                </div>
                                @elseif ($message->sent_from === 0)
                                <div class="mx-2 my-6 flex flex-col">
                                    <input type=checkbox name="messages[]" class="mr-auto" value="{{ $message->id }}">
                                    <p class="mr-auto flex p-2 rounded bg-gray-200 box-border lg:max-w-2xl md:max-w-lg max-w-md">
                                        {!! $message->body !!}
                                    </p>
                                    <div class="mr-auto">
                                        <small>{{ date('Y-m-d H:i', strtotime($message->sent_time)) }}</small>
                                    </div>
                                </div>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                        {{-- <textarea type="text" id="chatMessage" name="chatMessage"
                            class="chatMessage w-full bg-gray-100 bg-opacity-50 rounded border border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 text-base outline-none text-gray-700 my-4 py-1 px-3 leading-8 transition-colors duration-200 ease-in-out"></textarea> --}}
                        <div class="flex justify-around">
                            <button type="button" class="bg-gray-300 border-0 py-2 px-8 focus:outline-none hover:bg-gray-400 rounded text-lg" onClick="history.back()">戻る</button>
                            <button type="button" href="" onclick="deletePost(this)" data-contactusers-id="{{ $contactUsersId->first()->id }}" data-company-id="{{ $company->id }}" data-id="{{ $user->id }}" class="post text-center text-white bg-red-500 border-0 py-2 px-8 focus:outline-none hover:bg-red-600 rounded text-lg">削除</button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function deletePost(e) {
            'use strict';
            if (confirm('本当に削除してもいいですか?')) {
                document.getElementById('delete_' + e.dataset.id).submit();
            }
        }
    </script>
</x-app-layout>