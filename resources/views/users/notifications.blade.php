@extends('layouts.app')

@section('title_full', __('Notifications') . ' - ' . $user->first_name . ' ' . $user->last_name)

@if ($user->id == Auth::user()->id)
    @section('body_attrs')
        @parent data-own_profile="true"
    @endsection
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Notifications') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
        <div class="row">
            <div class="col-md-11 col-lg-9">
                <form method="POST" action="" class="user-subscriptions">
                    {{ csrf_field() }}
                    @include('users/subscriptions_table')
                    <div class="form-group margin-top">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save Notifications') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    notificationsInit();

    $(document).ready(function () {
        let exist = false;
        const def = location.pathname.replaceAll("/", "");
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            for(let j = 1; j <= 4; j++) {
                for(let k = 1; k <= 4; k++) {
                    if(key == `${def}monday_${j}${k}`) {
                        exist = true;
                        break;
                    }
                }
                if(exist) break;
            }
        }

        if(!exist) {
            for(i = 1; i <= 4; i++) {
                for(j = 1; j <=4; j++) {
                    if(localStorage.getItem(`monday_${i}${j}`) != null) {
                        localStorage.setItem(`${def}monday_${i}${j}`, 'true');
                    }
                }
            }
        }

        $('.checkbox-state').each(function () {
            const id = $(this).attr('id');
            const key = location.pathname.replaceAll("/", "") + id;
            const savedState = localStorage.getItem(key);
            if (savedState !== null) {
                $(this).prop('checked', savedState === 'true');
            }
        });

        // Save state to localStorage on change
        $('.checkbox-state').on('change', function () {
            const id = $(this).attr('id');
            const key = location.pathname.replaceAll("/", "") + id;
            localStorage.setItem(key, $(this).is(':checked'));
        });
    });
@endsection