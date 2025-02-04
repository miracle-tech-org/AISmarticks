@extends('layouts.app')

@section('title_full', 'AI Routing Rules')

@section('content')
    <div class="section-heading margin-bottom">
        {{ __('Routing Rules') }}
    </div>
    <br>

    <div class="container">
        <div class="rules-container">
            <a href="{{ route('routing.create') }}" class="btn btn-primary add-rule">
                {{ __('Add Rule') }}
            </a>

            <br>
            <br>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Conditions</th>
                        <th>Priority</th>
                        <th>Team</th>
                        <th>Required Skills</th>
                        <th>Status</th>
                        <th>Edit/Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr>
                            <td>{{ $rule->name }}</td>
                            <td>
                                @if (!empty($rule->conditions))
                                    <ul>
                                        @foreach (json_decode($rule->conditions, true) as $key => $value)
                                            <li>{{ $key }}: {{ $value }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <em>No conditions</em>
                                @endif
                            </td>
                            <td>{{ $rule->priority }}</td>
                            <td>{{ $rule->team->first_name ?? 'No Team' }}</td>
                            <td>
                                @if (!empty($rule->required_skills))
                                    <ul>
                                        @foreach (json_decode($rule->required_skills, true) as $skill)
                                            <li>{{ $skill }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <em>No skills required</em>
                                @endif
                            </td>
                            <td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <!-- Add action buttons here (e.g., edit, delete) -->
                                <a href="{{ route('routing.edit', $rule->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('routing.destroy', $rule->id) }}" method="POST"
                                    style="display:inline;">
                                    {{ csrf_field() }}
                                    <button type="submit" class="delete-btn btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this routing rule?');">Delete</button>
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
                if (!confirm('Are you sure you want to delete this routing rule?')) {
                    event.preventDefault();
                }
            });
        });
    }
</script>
