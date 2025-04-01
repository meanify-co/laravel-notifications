<x-mail::layout>
    <x-slot:header>
        <h1 style="font-family: {{ $font_family ?? 'Arial' }}; color: {{ $primary_color ?? '#0047ab' }}">
            {{ $header_title ?? 'App Notification' }}
        </h1>
    </x-slot:header>

    {!! $content !!}

    <x-slot:footer>
        <p style="font-size: 12px; color: #888;">
            {{ $footer_text ?? '© 2025 Meanify. All rights reserved.' }}
        </p>
    </x-slot:footer>
</x-mail::layout>
