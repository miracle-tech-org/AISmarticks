@extends('layouts.app')
@section('title_full', 'Create Routing Rule')

@section('content')
    <div class="section-heading">
        <h2 class="section-title">{{ __('Create New Routing Rule') }}</h2>
    </div>

    <div class="container">
        <div class="rules-container">
            <form action="{{ route('routing.store') }}" method="post" class="container">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="conditions">Condition</label>
                    <select class="form-control" id="conditions" name="conditions[]" required multiple>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <input type="text" class="form-control" id="priority" name="priority" required>
                </div>

                <div class="form-group">
                    <label for="team_id">Team</label>
                    <select name="team_id" id="team_id" class="form-control">
                        @foreach ($teams as $team)
                            <option value="{{$team->id}}">{{$team->first_name}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="required_skills">Required Skills</label>
                    <select class="form-control" id="required_skills" name="required_skills[]" required multiple>
                    </select>
                </div>

                <div class="form-group">
                    <label for="is_active">Active State</label>
                    <select class="form-control" id="is_active" name="is_active" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create Routing Rule</button>
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
    }
</script>
