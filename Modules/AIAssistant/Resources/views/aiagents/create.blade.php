@extends('layouts.app')

@section('title_full', "AIAssistant".' - '.$mailbox->name)

@section('sidebar')
@include('partials/sidebar_menu_toggle')
@include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading margin-bottom">
    AI Assistant Create
</div>

<div class="col-xs-12">
    <form method="POST" action="{{ route('aiagents.store', ['mailbox_id'=>$mailbox->id]) }}">
        {{ csrf_field() }}
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="openai_api_key">OpenAI API Key</label>
            <input type="text" class="form-control" id="openai_api_key" name="openai_api_key" required>
        </div>
        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" class="form-control" id="model" name="model" required>
        </div>
        <div class="form-group">
            <label for="system_prompt">System Prompt</label>
            <textarea class="form-control" id="system_prompt" name="system_prompt"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create AI Agent</button>
    </form>
</div>
@endsection