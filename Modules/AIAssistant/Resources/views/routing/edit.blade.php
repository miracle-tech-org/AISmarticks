@extends('layouts.app')
@section('title_full', 'Edit Routing Rule')
@section('sidebar')
@include('partials/sidebar_menu_toggle')
@include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading margin-bottom">
        {{ __('Edit Routing Rule') }}
    </div>

    <div class="container">
        <div class="rules-container">
            <form action="{{route('routing.update', ['id' => $rule->id, 'mailbox_id' => $mailbox->id])}}" method="post" class="container">
                {{ csrf_field() }}
                <input type="hidden" name="id" value="{{$rule->id}}">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $rule->name }}"
                        required>
                </div>

                <div class="form-group">
                    <label for="conditions">Condition</label>
                    <select class="form-control" id="conditions" name="conditions[]" required multiple>
                        @if (!empty($rule->conditions))
                            @foreach (json_decode($rule->conditions, true) as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <input type="text" class="form-control" id="priority" name="priority" required
                        value="{{ $rule->priority }}">
                </div>

                <div class="form-group">
                    <label for="team_id">Team</label>
                    <select name="team_id" id="team_id" class="form-control">
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->first_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="required_skills">Required Skills</label>
                    <select class="form-control" id="required_skills" name="required_skills[]" required multiple>
                        @if (!empty($rule->required_skills))
                            @foreach (json_decode($rule->required_skills, true) as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-group">
                    <label for="is_active">Active State</label>
                    <select class="form-control" id="is_active" name="is_active" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Routing Rule</button>
            </form>
        </div>
    </div>

@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    window.onload = function() {
        $('#conditions').select2({
            placeholder: "Select conditions",
            allowClear: true,
            tags: true, // Enables adding new options
        });

        $('#required_skills').select2({
            placeholder: "Select skills",
            allowClear: true,
            tags: true, // Enables adding new options
        });

        setTimeout(() => {
            const conditions = JSON.parse({!! json_encode($rule->conditions) !!})
            $('#conditions').val(conditions).trigger('change');

            const required_skills = JSON.parse({!! json_encode($rule->required_skills) !!})
            $('#required_skills').val(required_skills).trigger('change');

            $("#is_active").val("{{$rule->is_active}}");
            $("#team_id").val("{{$rule->team_id}}");
        }, 1000);
    }
</script>
