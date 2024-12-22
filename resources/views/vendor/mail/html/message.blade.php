@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            <a href="{{ \Config::get('app.freescout_url') }}" style="border: 0; color: #8c93a0; text-decoration: underline">{{ \Config::get('app.name') }}</a> — {{ "SMATRICKS - Making YOU available for success" }}
        @endcomponent
    @endslot
@endcomponent
