@extends('layouts.app')

@section('title', __('Routing Rules'))

@section('content')
    <div class="section-heading">
        <h2 class="section-title">{{ __('Routing Rules') }}</h2>
        <button type="button" class="btn btn-primary add-rule">
            {{ __('Add Rule') }}
        </button>
    </div>

    <div class="container">
        <div class="alert alert-info">
            {{ __('Rules are evaluated in order of priority. First matching rule will be applied.') }}
        </div>

        <div class="rules-container">
            @foreach ($rules as $rule)
                <div class="card mb-4 rule-card" data-rule-id="{{ $rule->id }}">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">{{ $rule->name }}</h3>
                            <div class="rule-actions">
                                <button type="button" class="btn btn-sm btn-info test-rule"
                                    data-rule-id="{{ $rule->id }}">
                                    {{ __('Test') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-rule">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('my-improvements.routing.update', $rule) }}"
                            class="rule-form">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Name') }}</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ $rule->name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>{{ __('Priority') }}</label>
                                        <input type="number" name="priority" class="form-control"
                                            value="{{ $rule->priority }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>{{ __('Status') }}</label>
                                        <select name="is_active" class="form-control">
                                            <option value="1" {{ $rule->is_active ? 'selected' : '' }}>
                                                {{ __('Active') }}
                                            </option>
                                            <option value="0" {{ !$rule->is_active ? 'selected' : '' }}>
                                                {{ __('Inactive') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Team Assignment') }}</label>
                                        <select name="team_id" class="form-control" required>
                                            @foreach ($teams as $team)
                                                <option value="{{ $team->id }}"
                                                    {{ $rule->team_id == $team->id ? 'selected' : '' }}>
                                                    {{ $team->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Required Skills') }}</label>
                                        <select name="required_skills[]" class="form-control select2-tags" multiple>
                                            @foreach ($skills ?? [] as $skill)
                                                <option value="{{ $skill }}"
                                                    {{ in_array($skill, $rule->required_skills ?? []) ? 'selected' : '' }}>
                                                    {{ $skill }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="conditions-section mt-3">
                                <h4>{{ __('Conditions') }}</h4>
                                <div class="conditions-container">
                                    @foreach ($rule->conditions ?? [] as $index => $condition)
                                        <div class="condition-row mb-2">
                                            <div class="input-group">
                                                <select name="conditions[{{ $index }}][field]" class="form-control">
                                                    <option value="priority"
                                                        {{ $condition['field'] === 'priority' ? 'selected' : '' }}>
                                                        {{ __('Priority') }}
                                                    </option>
                                                    <option value="category"
                                                        {{ $condition['field'] === 'category' ? 'selected' : '' }}>
                                                        {{ __('Category') }}
                                                    </option>
                                                    <option value="tag"
                                                        {{ $condition['field'] === 'tag' ? 'selected' : '' }}>
                                                        {{ __('Tag') }}
                                                    </option>
                                                </select>
                                                <select name="conditions[{{ $index }}][operator]"
                                                    class="form-control">
                                                    <option value="="
                                                        {{ $condition['operator'] === '=' ? 'selected' : '' }}>
                                                        {{ __('Equals') }}
                                                    </option>
                                                    <option value="in"
                                                        {{ $condition['operator'] === 'in' ? 'selected' : '' }}>
                                                        {{ __('Contains') }}
                                                    </option>
                                                </select>
                                                <input type="text" name="conditions[{{ $index }}][value]"
                                                    class="form-control" value="{{ $condition['value'] }}">
                                                <div class="input-group-append">
                                                    <button type="button"
                                                        class="btn btn-danger remove-condition">×</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-secondary add-condition mt-2">
                                    {{ __('Add Condition') }}
                                </button>
                            </div>

                            <div class="form-buttons mt-3">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save Changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Select2
                $('.select2-tags').select2({
                    tags: true,
                    tokenSeparators: [',', ' ']
                });

                // Add new rule
                document.querySelector('.add-rule').addEventListener('click', function() {
                    const template = document.querySelector('#rule-template').content.cloneNode(true);
                    document.querySelector('.rules-container').insertBefore(
                        template,
                        document.querySelector('.rules-container').firstChild
                    );

                    // Initialize Select2 for new rule
                    template.querySelectorAll('.select2-tags').forEach(select => {
                        $(select).select2({
                            tags: true,
                            tokenSeparators: [',', ' ']
                        });
                    });
                });

                // Delete rule
                document.querySelectorAll('.delete-rule').forEach(button => {
                    button.addEventListener('click', function() {
                        if (confirm('{{ __('Are you sure you want to delete this rule?') }}')) {
                            const ruleId = this.closest('.rule-card').dataset.ruleId;
                            fetch(`{{ url('my-improvements/routing') }}/${ruleId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    });
                });

                // Test rule
                document.querySelectorAll('.test-rule').forEach(button => {
                    button.addEventListener('click', function() {
                        const ruleId = this.dataset.ruleId;

                        // Add loading state
                        this.disabled = true;
                        this.innerHTML = '{{ __('Testing...') }}';

                        fetch(`{{ url('my-improvements/routing') }}/${ruleId}/test`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                            })
                            .catch(() => {
                                alert('{{ __('Test failed') }}');
                            })
                            .finally(() => {
                                // Reset button
                                this.disabled = false;
                                this.innerHTML = '{{ __('Test') }}';
                            });
                    });
                });

                // Add condition
                document.querySelectorAll('.add-condition').forEach(button => {
                    button.addEventListener('click', function() {
                        const template = document.querySelector('#condition-template').content
                            .cloneNode(true);
                        this.closest('.conditions-section')
                            .querySelector('.conditions-container')
                            .appendChild(template);
                    });
                });

                // Remove condition
                document.querySelectorAll('.remove-condition').forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('.condition-row').remove();
                    });
                });
            });
        </script>

        <template id="rule-template">
            <div class="card mb-4 rule-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">{{ __('New Rule') }}</h3>
                        <div class="rule-actions">
                            <button type="button" class="btn btn-sm btn-danger delete-rule">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('my-improvements.routing.store') }}" class="rule-form">
                        @csrf
                        {{-- Form fields same as edit form --}}
                    </form>
                </div>
            </div>
        </template>

        <template id="condition-template">
            <div class="condition-row mb-2">
                <div class="input-group">
                    <select name="conditions[][field]" class="form-control">
                        <option value="priority">{{ __('Priority') }}</option>
                        <option value="category">{{ __('Category') }}</option>
                        <option value="tag">{{ __('Tag') }}</option>
                    </select>
                    <select name="conditions[][operator]" class="form-control">
                        <option value="=">{{ __('Equals') }}</option>
                        <option value="in">{{ __('Contains') }}</option>
                    </select>
                    <input type="text" name="conditions[][value]" class="form-control">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-condition">×</button>
                    </div>
                </div>
            </div>
        </template>
    @endpush

    @push('styles')
        <style>
            .rule-card {
                position: relative;
            }

            .rule-actions {
                position: absolute;
                top: 1rem;
                right: 1rem;
            }

            .condition-row {
                margin-bottom: 0.5rem;
            }

            .conditions-container {
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 0.25rem;
            }
        </style>
    @endpush
@endsection
