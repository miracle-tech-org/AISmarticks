<div class="sidebar-block">
    <div class="sidebar-block-header">
        <img src="https://www.vectorlogo.zone/logos/monday/monday-ar21.svg" alt="">
    </div>
    <div class="sidebar-block-body">
        @if (!empty($mondayItems))
            <div class="monday-items">
                @foreach ($mondayItems as $item)
                    <div class="monday-item">
                        <h4>{{ $item['name'] }}</h4>
                        <div class="monday-item-details">
                            @foreach ($item['column_values'] as $column)
                                @if (!empty($column['text']))
                                    <div class="monday-column">
                                        <strong>{{ $column['text'] }}</strong> 
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="monday-actions">
                            <a href="{{ $item['url'] }}" target="_blank" class="btn btn-xs btn-default">
                                <i class="fa fa-external-link"></i> {{ __('View in Monday') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-section">
                <i class="fa fa-info-circle"></i> {{ __('No Monday.com data found for this customer') }}
            </div>
        @endif
    </div>
</div>

<style>
    .monday-item {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .monday-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .monday-item h4 {
        margin-top: 0;
        margin-bottom: 10px;
        font-weight: 600;
    }
    .monday-column {
        margin-bottom: 5px;
    }
    .monday-actions {
        margin-top: 10px;
        display: flex;
        gap: 5px;
    }
    .monday-quick-actions {
        display: inline-block;
    }
</style>

<script>
$(document).ready(function() {
    $('.monday-quick-action').on('click', function(e) {
        e.preventDefault();
        
        var actionId = $(this).data('action');
        var itemId = $(this).data('item-id');
        
        // Show loading indicator
        showFloatingAlert('info', '{{ __("Processing action...") }}');
        
        // Make AJAX request to perform the action
        $.ajax({
            type: 'POST',
            data: {
                action_id: actionId,
                item_id: itemId,
                conversation_id: {{ $conversation->id }}
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showFloatingAlert('success', response.message);
                // Reload the widget
                reloadSidebarWidget();
            },
            error: function(xhr) {
                var message = xhr.responseJSON ? xhr.responseJSON.message : '{{ __("An error occurred") }}';
                showFloatingAlert('error', message);
            }
        });
    });
    
    function reloadSidebarWidget() {
        $.ajax({
            type: 'GET',
            success: function(response) {
                $('.sidebar-block.monday-widget').replaceWith(response);
            }
        });
    }
});
</script>
