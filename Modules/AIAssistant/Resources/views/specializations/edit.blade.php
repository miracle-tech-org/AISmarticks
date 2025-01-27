@extends('layouts.app')

@section('title_full', __('Update Agent Specializations'))
@section('sidebar')
@include('partials/sidebar_menu_toggle')
@include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Update Agent Specializations') }}
    </div>

    <br>
    
    <div class="container">
        <div class="rules-container">
            <form action="{{ route('specializations.update', ['id' => $agentId, 'mailbox_id' => $mailbox->id]) }}" method="post" class="container">
                {{ csrf_field() }}
                <input type="hidden" name="id" value="{{$spec->id}}">
                <div class="form-group">
                    <label for="skill">Skill</label>
                    <input type="text" class="form-control" id="skill" name="skill" required value="{{$spec->skill}}">
                </div>

                <div class="form-group">
                    <label for="proficiency_level">Level</label>
                    <select name="proficiency_level" id="proficiency_level" class="form-control">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="is_primary">Primary</label>
                    <select name="is_primary" id="is_primary" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>

@endsection

<script>
    window.onload = function() {
        $("#proficiency_level").val("{{$spec->proficiency_level}}")
        $("#is_primary").val("{{$spec->is_primary}}")
    }
</script>
