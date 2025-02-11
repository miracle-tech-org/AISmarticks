@extends('layouts.app')

@section('title_full', 'AIAssistant Edit')

@section('content')
    <div class="section-heading margin-bottom">
        AI Assistant Edit
    </div>

    <div class="col-xs-12">
        <form method="POST" action="{{ route('aiagents.update', $aiAgent->id) }}">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $aiAgent->name }}" required>
            </div>
            <div class="form-group">
                <label for="openai_api_key">OpenAI API Key</label>
                <input type="text" class="form-control" id="openai_api_key" name="openai_api_key"
                    value="{{ $aiAgent->openai_api_key }}" required>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" class="form-control" id="model" name="model" value="{{ $aiAgent->model }}"
                    required>
            </div>
            <div class="form-group">
                <label for="domain_specific_responses">Domain Specific Responses</label>
                <input type="text" class="form-control" id="domain_specific_responses" name="domain_specific_responses"
                    value="{{ $aiAgent->domain_specific_responses }}" required />
            </div>
            <div class="form-group">
                <label for="system_prompt">System Prompt</label>
                <textarea class="form-control" id="system_prompt" name="system_prompt" rows="10">{{ $aiAgent->system_prompt }}</textarea>
            </div>
            <div class="form-group">
                <label for="faqsFile">FAQ</label>
                <input id="faqsFile" type="file" accept=".txt" />
                <br>
                <textarea class="form-control" id="faqs" name="faqs" rows="10">{{ $aiAgent->faqs }}</textarea>
                <p class="text-muted mt-2 small">
                    Supported file types: .jpg, .png, .pdf. Maximum size: 2MB.
                </p>
            </div>
            <div class="form-group">
                <label for="response_mode">Response Mode</label>
                <select class="form-control" name="response_mode" id="response_mode" required>
                    <option value="suggest">Suggest</option>
                    <option value="auto">Auto</option>
                    <option value="both">Both</option>
                </select>
            </div>

            <div class="form-group">
                <label for="monthly_limit">Monthly Usage Limit</label>
                <input type="text" name="monthly_limit" id="monthly_limit" value="{{ $aiAgent->monthly_limit }}"
                    class="form-control" value="10000">
            </div>

            <div class="form-group">
                <label for="monthly_usage">Current Usage of Month</label>
                <input type="text" name="monthly_usage" readonly id="monthly_usage" value="{{ $aiAgent->monthly_usage }}"
                    class="form-control">
            </div>

            <div class="form-group">
                <label for="monthly_alert_threshold">Usage Alert Threshold of Month (%)</label>
                <input type="text" id="monthly_alert_threshold" name="monthly_alert_threshold"
                    value="{{ $aiAgent->monthly_alert_threshold }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="daily_limit">Daily Usage Limit</label>
                <input type="text" name="daily_limit" id="daily_limit" class="form-control"
                    value="{{ $aiAgent->daily_limit }}">
            </div>

            <div class="form-group">
                <label for="daily_usage">Current Usage of Day</label>
                <input type="text" name="daily_usage" readonly id="daily_usage" class="form-control"
                    value="{{ $aiAgent->daily_usage }}">
            </div>

            <div class="form-group">
                <label for="daily_alert_threshold">Usage Alert Threshold of Day (%)</label>
                <input type="text" id="daily_alert_threshold" name="daily_alert_threshold"
                    value="{{ $aiAgent->daily_alert_threshold }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="user_id">Response Mode</label>
                <select class="form-control" name="user_id" id="user_id" required>
                    <option value="">Click to Select User</option>
                    @foreach ($users as $item)
                        <option value="{{$item->id}}">{{$item->first_name}} {{$item->last_name}}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update AI Agent</button>
        </form>
    </div>
@endsection

<script>
    window.onload = function() {
        document.getElementById('faqsFile').addEventListener('change', function() {
            var files = document.getElementById("faqsFile").files; // Access the files property
            if (files.length === 0) {
                return; // Exit if no file is selected
            }

            if (files[0] && files[0].type !== "text/plain") {
                alert("Please select only txt file.");
                return;
            }

            const maxSize = 2 * 1024 * 1024;
            if (files[0].size > maxSize) {
                alert("The selected file is too large. Please select a file smaller than 2MB.");
                return;
            }

            const reader = new FileReader();

            reader.onload = function(e) {
                document.getElementById('faqs').value = e.target.result; // Set file content to textarea
            };

            reader.readAsText(files[0]); // Read the first selected file as text
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById("response_mode").value = @json($aiAgent->response_mode);
        document.getElementById("user_id").value = @json($aiAgent->user_id);
    });
</script>
