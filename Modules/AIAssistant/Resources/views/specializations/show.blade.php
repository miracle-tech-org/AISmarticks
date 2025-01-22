@extends('layouts.app')

@section('title_full', __('Edit Agent Specializations'))

@section('content')
    <div class="section-heading">
        {{ __('Edit Agent Specializations') }}
    </div>
    <br>
    <div class="container">
        <div class="rules-container">
            <a href="{{route('specializations.create', $agent->id)}}" class="btn btn-primary add-rule">
                {{ __('Create New') }}
            </a>
            <br>
            <br>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Skill</th>
                        <th>Level</th>
                        <th>Primary</th>
                        <th>Edit/Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agent->specializations as $specialization)
                        <tr>
                            <td>{{ $agent->first_name }}</td>
                            <td>{{ $specialization->skill }}</td>
                            <td>{{ $specialization->proficiency_level }}</td>
                            <td>{{ $specialization->is_primary ? 'Yes' : 'No' }}</td>
                            <td>
                                <a href="{{ route('specializations.edit', ['id' => $agent->id, 'specId' => $specialization->id]) }}"
                                    class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('specializations.destroy', $specialization->id) }}" method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirmDeletion();">
                                    {{ csrf_field() }}
                                    <button type="submit" class="delete-btn btn btn-danger btn-sm">Delete</button>
                                </form>
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
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to delete this?')) {
                    event.preventDefault();
                }
            });
        });
    }
</script>
