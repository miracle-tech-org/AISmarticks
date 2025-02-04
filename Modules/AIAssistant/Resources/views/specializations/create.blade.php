@extends('layouts.app')

@section('title_full', __('Edit Agent Specializations'))

@section('content')
    <div class="section-heading">
        {{ __('Create Agent Specializations') }}
    </div>

    <br>
    <div class="container">
        <div class="rules-container">
            <form action="{{ route('specializations.store', $agentId) }}" method="post" class="container">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="skill">Skill</label>
                    <input type="text" class="form-control" id="skill" name="skill" required>
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

                <button type="submit" class="btn btn-primary">Create New</button>
            </form>
        </div>
    </div>

@endsection

<script>
    window.onload = function() {

    }
</script>
