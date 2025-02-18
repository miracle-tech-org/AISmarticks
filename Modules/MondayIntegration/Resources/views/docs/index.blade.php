@extends('layouts.app')

@section('title', __('Monday.com Documentation'))

@section('content')
<div class="section-heading">
    {{ __('Monday.com Integration Documentation') }}
</div>

<div class="row-container">
    <div class="col-md-3">
        <!-- Documentation Navigation -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Documentation') }}</h3>
            </div>
            <div class="list-group">
                <a href="{{ route('monday.docs', ['page' => 'getting-started']) }}" 
                   class="list-group-item {{ $page == 'getting-started' ? 'active' : '' }}">
                    {{ __('Getting Started') }}
                </a>
                <a href="{{ route('monday.docs', ['page' => 'configuration']) }}" 
                   class="list-group-item {{ $page == 'configuration' ? 'active' : '' }}">
                    {{ __('Configuration') }}
                </a>
                <a href="{{ route('monday.docs', ['page' => 'field-mapping']) }}" 
                   class="list-group-item {{ $page == 'field-mapping' ? 'active' : '' }}">
                    {{ __('Field Mapping') }}
                </a>
                <a href="{{ route('monday.docs', ['page' => 'webhooks']) }}" 
                   class="list-group-item {{ $page == 'webhooks' ? 'active' : '' }}">
                    {{ __('Webhooks') }}
                </a>
                <a href="{{ route('monday.docs', ['page' => 'troubleshooting']) }}" 
                   class="list-group-item {{ $page == 'troubleshooting' ? 'active' : '' }}">
                    {{ __('Troubleshooting') }}
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <!-- Documentation Content -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{{ $title }}</h3>
            </div>
            <div class="panel-body documentation-content">
                {!! $content !!}
            </div>
        </div>
    </div>
</div>

@push('css')
<style>
    .documentation-content {
        font-size: 14px;
        line-height: 1.6;
    }

    .documentation-content h2 {
        margin-top: 30px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .documentation-content h3 {
        margin-top: 25px;
        margin-bottom: 10px;
    }

    .documentation-content pre {
        background: #f8f8f8;
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 15px;
        margin: 15px 0;
    }

    .documentation-content code {
        background: #f8f8f8;
        padding: 2px 4px;
        border-radius: 3px;
        color: #333;
    }

    .documentation-content ul,
    .documentation-content ol {
        margin-bottom: 15px;
    }

    .documentation-content img {
        max-width: 100%;
        height: auto;
        margin: 15px 0;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .documentation-content blockquote {
        border-left: 4px solid #5bc0de;
        background: #f8f8f8;
        margin: 15px 0;
        padding: 15px;
    }

    .documentation-content table {
        width: 100%;
        margin: 15px 0;
        border-collapse: collapse;
    }

    .documentation-content table th,
    .documentation-content table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .documentation-content table th {
        background: #f5f5f5;
    }
</style>
@endpush
