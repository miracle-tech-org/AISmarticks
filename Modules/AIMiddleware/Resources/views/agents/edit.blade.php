@extends('layouts.app')

@section('title', __('Edit AI Agent'))

@section('content')
    <div class="section-heading">
        <h2 class="section-title">{{ __('Edit AI Agent') }}</h2>
    </div>

    <div class="container">
        <form method="POST" action="{{ route('my-improvements.agents.update', $agent->id) }}">
            @csrf
            @method('PUT')

            {{-- Basic Configuration --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Basic Configuration') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ old('name', $agent->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="model">{{ __('Model') }}</label>
                        <select name="model" id="model" class="form-control" required>
                            <option value="gpt-4" {{ $agent->model === 'gpt-4' ? 'selected' : '' }}>GPT-4</option>
                            <option value="gpt-3.5-turbo" {{ $agent->model === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5
                                Turbo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="openai_api_key">{{ __('OpenAI API Key') }}</label>
                        <div class="input-group">
                            <input type="password" name="openai_api_key" id="openai_api_key" class="form-control"
                                value="{{ old('openai_api_key', $agent->openai_api_key) }}" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info test-connection">
                                    {{ __('Test Connection') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Response Configuration --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Response Configuration') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="system_prompt">{{ __('System Prompt') }}</label>
                        <textarea name="system_prompt" id="system_prompt" class="form-control" rows="4">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
                        <small class="form-text text-muted">
                            {{ __('Define how the AI agent should behave and respond.') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Domain Specific Responses') }}</label>
                        <div id="domain-responses">
                            @foreach ($agent->domain_specific_responses ?? [] as $index => $response)
                                <div class="domain-response mb-3">
                                    <div class="input-group">
                                        <input type="text" name="domain_specific_responses[{{ $index }}][domain]"
                                            class="form-control" placeholder="Domain"
                                            value="{{ $response['domain'] ?? '' }}">
                                        <textarea name="domain_specific_responses[{{ $index }}][responses]" class="form-control"
                                            placeholder="Responses (one per line)" rows="2">{{ implode("\n", $response['responses'] ?? []) }}</textarea>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-response">×</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-secondary add-response">
                            {{ __('Add Response') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tag Configuration --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Tag Configuration') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="default_tags">{{ __('Default Tags') }}</label>
                        <select name="default_tags[]" id="default_tags" class="form-control select2-tags" multiple>
                            @foreach ($tagOptions as $tag)
                                <option value="{{ $tag }}"
                                    {{ in_array($tag, $agent->default_tags ?? []) ? 'selected' : '' }}>
                                    {{ $tag }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            {{ __('These tags will be available for the AI to use.') }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Status') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
                                {{ $agent->active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="active">
                                {{ __('Active') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                <a href="{{ route('my-improvements.agents.index') }}" class="btn btn-secondary">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Select2
                $('.select2-tags').select2({
                    tags: true,
                    tokenSeparators: [',', ' ']
                });

                // Test connection
                document.querySelector('.test-connection').addEventListener('click', function() {
                    // Add loading state
                    this.disabled = true;
                    this.innerHTML = '{{ __('Testing...') }}';

                    // Make test request
                    fetch('{{ route('my-improvements.agents.test', $agent->id) }}', {
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
                        .catch(error => {
                            alert('{{ __('Connection test failed') }}');
                        })
                        .finally(() => {
                            // Reset button
                            this.disabled = false;
                            this.innerHTML = '{{ __('Test Connection') }}';
                        });
                });

                // Domain response handlers
                document.querySelector('.add-response').addEventListener('click', function() {
                    const template = document.querySelector('#response-template').content.cloneNode(true);
                    document.querySelector('#domain-responses').appendChild(template);
                });

                document.querySelectorAll('.remove-response').forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('.domain-response').remove();
                    });
                });
            });
        </script>

        <template id="response-template">
            <div class="domain-response mb-3">
                <div class="input-group">
                    <input type="text" name="domain_specific_responses[][domain]" class="form-control" placeholder="Domain">
                    <textarea name="domain_specific_responses[][responses]" class="form-control" placeholder="Responses (one per line)"
                        rows="2"></textarea>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-response">×</button>
                    </div>
                </div>
            </div>
        </template>
    @endpush
@endsection
