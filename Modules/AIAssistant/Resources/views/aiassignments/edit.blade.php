<!-- Edit AI Agent Assignment Form with CSRF Token -->
<form method="POST" action="{{ route('aiagent.assignments.update', $assignment->id) }}">
    @csrf
    @method('PUT')
    <!-- Form Fields -->
    <div class="form-group">
        <label for="monthly_usage_limit">Monthly Usage Limit</label>
        <input type="number" class="form-control" id="monthly_usage_limit" name="monthly_usage_limit" value="{{ $assignment->monthly_usage_limit }}" required>
    </div>
    <div class="form-group">
        <label for="usage_alert_threshold">Usage Alert Threshold (%)</label>
        <input type="number" class="form-control" id="usage_alert_threshold" name="usage_alert_threshold" value="{{ $assignment->usage_alert_threshold }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Update AI Agent Assignment</button>
</form>
