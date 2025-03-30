<ul class="nav nav-tabs">
    <li {!! Route::currentRouteName() == 'monday.settings' ? 'class="active"' : '' !!}>
        <a href="{{ route('monday.settings', ['mailbox_id' => $mailbox->id]) }}">{{ __('General Settings') }}</a>
    </li>
    <li {!! Route::currentRouteName() == 'monday.mappings' ? 'class="active"' : '' !!}>
        <a href="{{ route('monday.mappings', ['mailbox_id' => $mailbox->id]) }}">{{ __('Field Mapping') }}</a>
    </li>
    <li {!! Route::currentRouteName() == 'monday.sidebar_settings' ? 'class="active"' : '' !!}>
        <a href="{{ route('monday.sidebar_settings', ['mailbox_id' => $mailbox->id]) }}">{{ __('Sidebar Widget') }}</a>
    </li>
    <li {!! Route::currentRouteName() == 'monday.queue_settings' ? 'class="active"' : '' !!}>
        <a href="{{ route('monday.queue_settings', ['mailbox_id' => $mailbox->id]) }}">{{ __('Queue Settings') }}</a>
    </li>
</ul>
