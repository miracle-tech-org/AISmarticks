@extends('layouts.app')

@section('title_full', __('Agent Specializations'))

@section('content')

    <div class="section-heading">
        {{ __('Agent Specializations') }}
    </div>

    <br>

    <div class="container">
        <div class="rules-container">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Skills & Levels</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agents as $agent)
                        <tr>
                            <td>{{ $agent->first_name }}</td>
                            <td>
                                <ul>
                                    @foreach ($agent->specializations as $specialization)
                                        <li>Skill: {{ $specialization->skill }} Level: {{ $specialization->proficiency_level }} Primary: {{$specialization->is_primary ? "Yes" : "No"}}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                <a href="{{ route('specializations.show', $agent->id) }}"
                                    class="btn btn-primary btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection

<script>
    window.onload = function() {

    }
</script>
