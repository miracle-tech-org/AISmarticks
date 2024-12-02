<!-- Display Conversation with Feedback Form -->
<div class="card">
    <div class="card-header">
        <h2>Conversation</h2>
    </div>
    <div class="card-body">
        <!-- Conversation Details -->
        <p>AI Agent: {{ $conversation->aiAgent->name }}</p>
        <p>User: {{ $conversation->user->name }}</p>
        <p>Message: {{ $conversation->message }}</p>

        <!-- Feedback Form -->
        <form method="POST" action="{{ route('conversations.update', $conversation->id) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="feedback">Feedback</label>
                <select class="form-control" id="feedback" name="feedback">
                    <option value="">Select Feedback</option>
                    <option value="1">Positive</option>
                    <option value="-1">Negative</option>
                    <option value="0">Neutral</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit Feedback</button>
        </form>
    </div>
</div>
