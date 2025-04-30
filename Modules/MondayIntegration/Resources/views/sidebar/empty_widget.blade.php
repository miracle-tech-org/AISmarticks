<div class="sidebar-block">
    <div class="sidebar-block-header">
        <img src="https://www.vectorlogo.zone/logos/monday/monday-ar21.svg" alt="">
    </div>
    <div class="sidebar-block-body">
        <div class="empty-section">
            <i class="fa fa-info-circle"></i> {{ __('No Monday.com data found for this customer') }}
        </div>
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
