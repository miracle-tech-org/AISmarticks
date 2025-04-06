@extends('layouts.app')
@section('title', __('Monday.com Board Creation'))
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ __('Create Monday.com Board') }}
                </div>
                <div class="card-body">
                    <div id="board-creation-app">
                        <!-- Alerts -->
                        <div class="alert alert-success" id="success-alert" style="display: none;"></div>
                        <div class="alert alert-danger" id="error-alert" style="display: none;"></div>

                        <!-- Form -->
                        <form id="board-creation-form">
                            <!-- Board Name -->
                            <div class="form-group">
                                <label for="board_name">{{ __('Board Name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="board_name" required>
                                <small class="form-text text-muted">{{ __('Enter a name for your new board') }}</small>
                            </div>

                            <!-- Board Visibility -->
                            <div class="form-group">
                                <label for="board_kind">{{ __('Board Visibility') }}</label>
                                <select class="form-control" id="board_kind">
                                    <option value="public">{{ __('Public') }}</option>
                                    <option value="private">{{ __('Private') }}</option>
                                    <option value="share">{{ __('Shareable') }}</option>
                                </select>
                                <small class="form-text text-muted">{{ __('Select the visibility level for your board') }}</small>
                            </div>

                            <!-- Use Template -->
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="use_template">
                                    <label class="custom-control-label" for="use_template">{{ __('Use a Template') }}</label>
                                </div>
                                <small class="form-text text-muted">{{ __('Create a board from an existing template') }}</small>
                            </div>

                            <!-- Select Template -->
                            <div class="form-group" id="template-section" style="display: none;">
                                <label for="template_id">{{ __('Select Template') }} <span class="text-danger">*</span></label>
                                <select class="form-control" id="template_id">
                                    <option value="">{{ __('Select a template...') }}</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">{{ __('Choose a template for your new board') }}</small>
                            </div>

                            <!-- Add Users -->
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="add_users">
                                    <label class="custom-control-label" for="add_users">{{ __('Add Users to Board') }}</label>
                                </div>
                                <small class="form-text text-muted">{{ __('Add users to your new board with specific permissions') }}</small>
                            </div>

                            <!-- Permission Level -->
                            <div class="form-group" id="permission-section" style="display: none;">
                                <label for="permission_level">{{ __('Permission Level') }}</label>
                                <select class="form-control" id="permission_level">
                                    <option value="view">{{ __('View Only') }}</option>
                                    <option value="edit">{{ __('Edit') }}</option>
                                    <option value="admin">{{ __('Admin') }}</option>
                                </select>
                                <small class="form-text text-muted">{{ __('Select the permission level for added users') }}</small>
                            </div>

                            <!-- Select Users -->
                            <div class="form-group" id="users-section" style="display: none;">
                                <label>{{ __('Select Users') }}</label>
                                <div id="loading-users" class="text-center" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">{{ __('Loading...') }}</span>
                                    </div>
                                </div>
                                <div id="users-container"></div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary" id="submit-button">
                                    {{ __('Create Board') }}
                                </button>
                            </div>
                        </form>

                        <!-- Created Board Info -->
                        <div id="created-board-info" style="display: none;" class="mt-4">
                            <div class="alert alert-info">
                                <h5>{{ __('Board Created Successfully') }}</h5>
                                <p>{{ __('Board ID') }}: <span id="board-id"></span></p>
                                <p>{{ __('Board Name') }}: <span id="board-name"></span></p>
                                <p>{{ __('Board Type') }}: <span id="board-type"></span></p>
                                <a id="board-link" target="_blank" class="btn btn-sm btn-primary">
                                    {{ __('Open Board in Monday.com') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formData = {
        board_name: '',
        board_kind: 'public',
        use_template: false,
        template_id: '',
        permission_level: 'view',
        user_ids: []
    };

    let users = [];
    let selectedUsers = [];
    let loading = false;
    let loadingUsers = false;

    // DOM Elements
    const successAlert = document.getElementById('success-alert');
    const errorAlert = document.getElementById('error-alert');
    const boardNameInput = document.getElementById('board_name');
    const boardKindSelect = document.getElementById('board_kind');
    const useTemplateCheckbox = document.getElementById('use_template');
    const templateSection = document.getElementById('template-section');
    const templateIdSelect = document.getElementById('template_id');
    const addUsersCheckbox = document.getElementById('add_users');
    const permissionSection = document.getElementById('permission-section');
    const permissionLevelSelect = document.getElementById('permission_level');
    const usersSection = document.getElementById('users-section');
    const usersContainer = document.getElementById('users-container');
    const loadingUsersDiv = document.getElementById('loading-users');
    const submitButton = document.getElementById('submit-button');
    const createdBoardInfo = document.getElementById('created-board-info');
    const boardIdSpan = document.getElementById('board-id');
    const boardNameSpan = document.getElementById('board-name');
    const boardTypeSpan = document.getElementById('board-type');
    const boardLink = document.getElementById('board-link');

    // Event Listeners
    boardNameInput.addEventListener('input', (e) => formData.board_name = e.target.value);
    boardKindSelect.addEventListener('change', (e) => formData.board_kind = e.target.value);
    useTemplateCheckbox.addEventListener('change', (e) => {
        formData.use_template = e.target.checked;
        templateSection.style.display = e.target.checked ? 'block' : 'none';
        templateIdSelect.required = e.target.checked;
    });
    templateIdSelect.addEventListener('change', (e) => formData.template_id = e.target.value);
    addUsersCheckbox.addEventListener('change', (e) => {
        if (e.target.checked && users.length === 0) {
            loadUsers();
        }
        toggleUsersSection(e.target.checked);
    });
    permissionLevelSelect.addEventListener('change', (e) => formData.permission_level = e.target.value);

    submitButton.addEventListener('click', (e) => {
        e.preventDefault();
        createBoard();
    });

    function toggleUsersSection(show) {
        usersSection.style.display = show ? 'block' : 'none';
        permissionSection.style.display = show ? 'block' : 'none';
    }

    function loadUsers() {
        loadingUsers = true;
        loadingUsersDiv.style.display = 'block';
        usersContainer.innerHTML = '';

        axios.get("{{ route('monday.board-creation.users') }}")
            .then(response => {
                loadingUsers = false;
                loadingUsersDiv.style.display = 'none';
                if (response.data.success) {
                    users = response.data.users;
                    renderUsers();
                } else {
                    showError(response.data.message || '{{ __('Failed to load users') }}');
                }
            })
            .catch(error => {
                loadingUsers = false;
                loadingUsersDiv.style.display = 'none';
                showError('{{ __('Failed to load users') }}');
            });
    }

    function renderUsers() {
        usersContainer.innerHTML = '';
        users.forEach(user => {
            const div = document.createElement('div');
            div.className = 'custom-control custom-checkbox';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'custom-control-input';
            input.id = 'user_' + user.id;
            input.value = user.id;

            const label = document.createElement('label');
            label.className = 'custom-control-label';
            label.htmlFor = 'user_' + user.id;
            label.textContent = `${user.name} (${user.email})`;

            input.addEventListener('change', (e) => {
                if (e.target.checked) {
                    selectedUsers.push(user.id);
                } else {
                    selectedUsers = selectedUsers.filter(id => id !== user.id);
                }
                formData.user_ids = selectedUsers;
            });

            div.appendChild(input);
            div.appendChild(label);
            usersContainer.appendChild(div);
        });
    }

    function createBoard() {
        loading = true;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Creating Board...') }}';

        axios.post("{{ route('monday.board-creation.create')}}", formData)
            .then(response => {
                loading = false;
                submitButton.disabled = false;
                submitButton.textContent = '{{ __('Create Board') }}';

                if (response.data.success) {
                    showSuccess(response.data.message);
                    resetForm();
                    displayCreatedBoard(response.data.board);
                } else {
                    showError(response.data.message || '{{ __('An error occurred') }}');
                }
            })
            .catch(error => {
                loading = false;
                submitButton.disabled = false;
                submitButton.textContent = '{{ __('Create Board') }}';

                if (error.response && error.response.data) {
                    if (error.response.data.errors) {
                        showError(error.response.data.errors.join('<br>'));
                    } else {
                        showError(error.response.data.message || '{{ __('An error occurred') }}');
                    }
                } else {
                    showError('{{ __('An error occurred while creating the board') }}');
                }
            });
    }

    function resetForm() {
        formData.board_name = '';
        formData.board_kind = 'public';
        formData.use_template = false;
        formData.template_id = '';
        formData.permission_level = 'view';
        formData.user_ids = [];

        boardNameInput.value = '';
        boardKindSelect.value = 'public';
        useTemplateCheckbox.checked = false;
        templateIdSelect.value = '';
        templateSection.style.display = 'none';
        templateIdSelect.required = false;
        addUsersCheckbox.checked = false;
        permissionLevelSelect.value = 'view';
        toggleUsersSection(false);
        usersContainer.innerHTML = '';
        selectedUsers = [];
        users = [];
    }

    function displayCreatedBoard(board) {
        boardIdSpan.textContent = board.id;
        boardNameSpan.textContent = board.name;
        boardTypeSpan.textContent = board.board_kind;
        boardLink.href = `https://monday.com/boards/${board.id}`;
        createdBoardInfo.style.display = 'block';
    }

    function showSuccess(message) {
        successAlert.innerHTML = message;
        successAlert.style.display = 'block';
        errorAlert.style.display = 'none';
    }

    function showError(message) {
        errorAlert.innerHTML = message;
        errorAlert.style.display = 'block';
        successAlert.style.display = 'none';
    }
});
</script>
@endsection