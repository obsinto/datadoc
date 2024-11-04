@props([
    'type' => $errors->any() ? 'error' : 'info', // Define o tipo automaticamente
    'title' => $errors->any() ? 'Erro ao processar a solicitação' : '',
    'message' => '',
    'items' => $errors->any() ? $errors->all() : []
])

@php
    $colors = [
        'info' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900',
            'border' => 'border-blue-500',
            'text' => 'text-blue-800 dark:text-blue-100',
            'icon' => 'text-blue-700 dark:text-blue-300'
        ],
        'error' => [
            'bg' => 'bg-red-100 dark:bg-red-900',
            'border' => 'border-red-500',
            'text' => 'text-red-800 dark:text-red-100',
            'icon' => 'text-red-700 dark:text-red-300'
        ],
        'success' => [
            'bg' => 'bg-green-100 dark:bg-green-900',
            'border' => 'border-green-500',
            'text' => 'text-green-800 dark:text-green-100',
            'icon' => 'text-green-700 dark:text-green-300'
        ]
    ];

    $currentColors = $colors[$type];
@endphp

<div
    class="w-full {{ $currentColors['bg'] }} border {{ $currentColors['border'] }} {{ $currentColors['text'] }} p-4 rounded-md my-4">
    <div class="flex items-start">
        @if($type === 'error')
            <svg class="w-5 h-5 mr-3 {{ $currentColors['icon'] }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                      clip-rule="evenodd"/>
            </svg>
        @elseif($type === 'success')
            <svg class="w-5 h-5 mr-3 {{ $currentColors['icon'] }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"/>
            </svg>
        @else
            <svg class="w-5 h-5 mr-3 {{ $currentColors['icon'] }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 4a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1z"
                      clip-rule="evenodd"/>
            </svg>
        @endif
        <div>
            @if($title)
                <h4 class="font-semibold {{ $currentColors['text'] }}">{{ $title }}</h4>
            @endif
            @if($message)
                <p class="{{ $currentColors['text'] }}">{{ $message }}</p>
            @endif
            @if(count($items) > 0)
                <ul class="list-disc list-inside mt-2">
                    @foreach($items as $item)
                        <li class="{{ $currentColors['text'] }}">{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
