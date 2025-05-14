<?php
    if (!isset($subscriptions_formname, $subscriptions)) $subscriptions_formname = "subscriptions";
?>
<table class="table" style="margin-bottom: 0;">
    <tr class="table-header">
        <th>
            @if ($person)
                {{ __('Notify :person when…', ['person' => $person]) }}
            @else
                {{ __('Notify me when…') }}
            @endif
        </th>
        <td class="text-center">{{ __('Email') }}<br/><input type="checkbox" class="sel-all" value="email"></td>
        <td class="text-center">{{ __('Browser') }}<br/><input type="checkbox" class="sel-all" value="browser"></td>
        <td class="text-center">{{ __('Mobile') }}<br/><small>(<a href="https://freescout.net/module/mobile-notifications/" target="_blank">Android</a> / <a href="https://freescout.net/module/mobile-notifications/" target="_blank">iOS</a>)</small><br/><input type="checkbox" class="sel-all" @if (!$mobile_available) disabled="disabled" @endif value="mobile"></td>
        @action('notifications_table.th')
    </tr>
    <tr>
        <td>{{ __('There is a new conversation') }}</td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_NEW_CONVERSATION]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_NEW_CONVERSATION }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_NEW_CONVERSATION, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __('A conversation is assigned to :person', ['person' => $person]) }}
            @else
                {{ __('A conversation is assigned to me') }}
            @endif
        </td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>{{ __('A conversation is assigned to someone else') }}</td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CONVERSATION_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CONVERSATION_ASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CONVERSATION_ASSIGNED, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __('A conversation :person is following is updated', ['person' => $person]) }}
            @else
                {{ __("A conversation I'm following is updated") }}
            @endif
        </td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>AI agent daily usage alert</td>
        <td class="subs-cb"><input type="checkbox" id="ai_11" checked class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="ai_12" checked class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="ai_13" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="ai_14" class="checkbox-state"/></td>
    </tr>
    <tr>
        <td>AI agent monthly usage alert</td>
        <td class="subs-cb"><input type="checkbox" id="ai_21" checked class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="ai_22" checked class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="ai_23" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="ai_24" class="checkbox-state"/></td>
    </tr>
    @action('notifications_table.general.append', ['person' => $person, 'subscriptions' => $subscriptions, 'mobile_available' => $mobile_available, 'subscriptions_formname' => $subscriptions_formname])
    <tr class="table-header">
        <th>
            @if ($person)
                {{ __("Notify :person when a customer replies…", ['person' => $person]) }}
            @else
                {{ __('Notify me when a customer replies…') }}
            @endif
        </th>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
    </tr>
    <tr>
        <td>{{ __('To an unassigned conversation') }}</td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __("To one of :person's conversations", ['person' => $person]) }}
            @else
                {{ __('To one of my conversations') }}
            @endif
        </td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>{{ __('To a conversation owned by someone else') }}</td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED, $subscriptions_formname, $subscriptions)
    </tr>
    <tr class="table-header">
        <th>
            @if ($person)
                {{ __("Notify :person when another :app_name user replies or adds a note…", ['person' => $person, 'app_name' => config('app.name')]) }}
            @else
                {{ __('Notify me when another :app_name user replies or adds a note…', ['app_name' => config('app.name')]) }}
            @endif
        </th>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
    </tr>
    <tr>
        <td>{{ __('To an unassigned conversation') }}</td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>
            @if ($person)
                {{ __("To one of :person's conversations", ['person' => $person]) }}
            @else
                {{ __('To one of my conversations') }}
            @endif
        </td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_MY]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_MY }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_USER_REPLIED_TO_MY, $subscriptions_formname, $subscriptions)
    </tr>
    <tr>
        <td>{{ __('To a conversation owned by someone else') }}</td>
        <td class="subs-cb subscriptions-email"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_EMAIL, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_EMAIL }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subs-cb subscriptions-browser"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_BROWSER, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_BROWSER }}][]" value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
        <td class="subs-cb subscriptions-mobile"><input type="checkbox" @include('users/is_subscribed', ['medium' => App\Subscription::MEDIUM_MOBILE, 'event' => App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED]) name="{{ $subscriptions_formname }}[{{ App\Subscription::MEDIUM_MOBILE }}][]" @if (!$mobile_available) disabled="disabled" @endif value="{{ App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"></td>
        @action('notifications_table.td', App\Subscription::EVENT_USER_REPLIED_TO_ASSIGNED, $subscriptions_formname, $subscriptions)
    </tr>

    <tr class="table-header">
        <th>
            @if ($person)
                {{ __("Notify :person when monday… ", ['person' => $person]) }}
            @else
                {{ __('Notify me when monday… ') }}
            @endif
        </th>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
        <td class="text-center">&nbsp;</td>
    </tr>
    <tr>
        <td>When some error is received and logged in Monday log</td>
        <td class="subs-cb"><input type="checkbox" id="monday_11" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_12" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_13" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_14" class="checkbox-state"/></td>
    </tr>
    <tr>
        <td>When some error was received while smarticks tried to push data to Monday</td>
        <td class="subs-cb"><input type="checkbox" id="monday_21" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_22" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_23" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_24" class="checkbox-state"/></td>
    </tr>
    <tr>
        <td>when some error was received while smarticks tried to pull data from Monday</td>
        <td class="subs-cb"><input type="checkbox" id="monday_31" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_32" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_33" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_34" class="checkbox-state"/></td>
    </tr>
    <tr>
        <td>when a timeout occurred from Monday</td>
        <td class="subs-cb"><input type="checkbox" id="monday_41" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_42" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_43" class="checkbox-state"/></td>
        <td class="subs-cb"><input type="checkbox" id="monday_44" class="checkbox-state"/></td>
    </tr>

</table>
