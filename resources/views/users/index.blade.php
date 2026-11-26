@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Users</li>
@endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h5 class="mb-0 fw-700">
            @if(auth()->user()->isShopAdmin() && $shopName)
                {{ $shopName }} — Sellers
            @else
                Users
            @endif
        </h5>
        <small style="color:var(--text-secondary);">
            @if(auth()->user()->isShopAdmin())
                Manage sellers in your shop
            @else
                Manage System Admins, Shop Admins and Sellers
            @endif
        </small>
    </div>
    <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addUserModal" id="btnNewUser">
        <i class="bi bi-person-plus-fill me-1"></i>
        @if(auth()->user()->isShopAdmin()) Add Seller @else Register User @endif
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="usersTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Name</th>
                    <th data-priority="3" class="min-tablet-l">Email</th>
                    <th data-priority="3" class="min-tablet-l">Phone</th>
                    <th data-priority="2" class="min-tablet-l">Role</th>
                    @if(auth()->user()->isOwner())
                    <th data-priority="2" class="min-tablet-l">Assigned Shop</th>
                    @endif
                    <th data-priority="2" class="min-tablet-l text-center" style="min-width: 90px;">Admin POS</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Add / Edit User Modal -->
@php
    $userModalErr = $errors->any() && in_array(old('_modal'), ['add_user', 'edit_user'], true);
    $userModalIsEdit = $userModalErr && old('_modal') === 'edit_user';
@endphp
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addUserModalLabel">
                    <i class="bi bi-person-plus-fill me-2" style="color:var(--accent-blue);"></i>
                    <span id="userModalTitleText">
                        @if($userModalIsEdit)
                            Edit User
                        @elseif(auth()->user()->isShopAdmin())
                            Add Seller — {{ auth()->user()->shop?->shop_name }}
                        @else
                            Register New User
                        @endif
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm" method="POST" action="{{ $userModalIsEdit ? old('_action', route('users.store')) : route('users.store') }}">
                @csrf
                <input type="hidden" name="_method" id="userFormMethod" value="{{ $userModalIsEdit ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_modal" id="userFormModalTag" value="{{ $userModalIsEdit ? 'edit_user' : 'add_user' }}">
                <input type="hidden" name="_action" id="userFormActionMirror" value="{{ $userModalIsEdit ? old('_action') : '' }}">
                <div class="modal-body">
                    @if($userModalErr)
                    <div class="alert alert-danger border-0 rounded-3 mb-3 py-2 px-3" style="font-size:.82rem;">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="userInputName" class="form-control" value="{{ $userModalErr ? old('name') : '' }}" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="userInputEmail" class="form-control" value="{{ $userModalErr ? old('email') : '' }}" placeholder="john@amstroom.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Phone Number</label>
                            <input type="text" name="phone" id="userInputPhone" class="form-control" value="{{ $userModalErr ? old('phone') : '' }}" placeholder="+255700000000">
                        </div>

                        @if(auth()->user()->isOwner())
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">System Role <span class="text-danger">*</span></label>
                            <select name="role" id="modalRoleSelect" class="form-select" required>
                                <option value="seller" {{ ($userModalErr ? old('role', 'seller') : 'seller') === 'seller' ? 'selected' : '' }}>Seller</option>
                                <option value="shop_admin" {{ $userModalErr && old('role') === 'shop_admin' ? 'selected' : '' }}>Shop Admin</option>
                                @if(auth()->user()->role === 'owner')
                                <option value="system_admin" {{ $userModalErr && old('role') === 'system_admin' ? 'selected' : '' }}>System Admin</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6" id="modalShopSelectWrapper">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Assign to Shop</label>
                            <select name="shop_id" id="modalShopSelect" class="form-select">
                                <option value="">None (Unassigned / Global)</option>
                                @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ $userModalErr && old('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->shop_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-none modal-system-admin-note" style="font-size:0.72rem;"><i class="bi bi-info-circle me-1"></i>System Admin operates globally across all shops.</small>
                        </div>
                        <div class="col-12" id="modalAllowStockWrapper" style="display: {{ ($userModalErr && old('role') === 'shop_admin') ? 'block' : 'none' }};">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_stock_addition" id="modalAllowStockAddition" value="1" {{ $userModalErr && old('allow_stock_addition') ? 'checked' : '' }} style="width:2.2em;height:1.2em;">
                                <label class="form-check-label ms-2 small fw-600" for="modalAllowStockAddition">Allow Owner Stock Addition</label>
                            </div>
                        </div>
                        @else
                        <input type="hidden" name="role" value="seller">
                        <input type="hidden" name="shop_id" value="{{ auth()->user()->shop_id }}">
                        @endif

                        <div class="col-md-6 {{ $userModalIsEdit ? '' : 'd-none' }}" id="modalStatusWrapper">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Account Status <span class="text-danger">*</span></label>
                            <select name="status" id="modalUserStatus" class="form-select" {{ $userModalIsEdit ? '' : 'disabled' }}>
                                <option value="active" {{ ($userModalErr ? old('status', 'active') : 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $userModalErr && old('status') === 'inactive' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>

                        <div class="col-12" id="modalAllowPosWrapper" style="display: {{ (!auth()->user()->isOwner() || ($userModalErr ? old('role', 'seller') : 'seller') === 'seller') ? 'block' : 'none' }};">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_pos_admin" id="modalAllowPosAdmin" value="1" {{ $userModalErr && old('allow_pos_admin') ? 'checked' : '' }} style="width:2.2em;height:1.2em;">
                                <label class="form-check-label ms-2 small fw-600" for="modalAllowPosAdmin">Allow Admin POS Access</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size:0.73rem;">Allows seller to negotiate prices down to buying price like an admin.</small>
                        </div>

                        <div class="col-12 {{ $userModalIsEdit ? 'd-none' : '' }}" id="modalDefaultPwdNotice">
                            <div class="alert alert-info border-0 py-2 px-3 mb-0" style="font-size:.8rem;">
                                <i class="bi bi-key me-1"></i> Default password is <strong>password</strong>. The user can change it after login.
                            </div>
                        </div>

                        <div class="d-none">
                            <input type="password" name="password" id="modalHiddenPwd" value="password" {{ $userModalIsEdit ? 'disabled' : 'required' }} minlength="6">
                            <input type="password" name="password_confirmation" id="modalHiddenPwdConfirm" value="password" {{ $userModalIsEdit ? 'disabled' : 'required' }} minlength="6">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">
                        <i class="bi bi-save me-1"></i> <span id="btnSaveUserText">{{ $userModalIsEdit ? 'Update User' : (auth()->user()->isShopAdmin() ? 'Add Seller' : 'Register User') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="viewUserModalLabel">
                    <i class="bi bi-person-badge me-2" style="color:var(--accent-blue);"></i>
                    <span id="viewUserTitle">User Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewUserBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading user details...</div>
            </div>
            <div class="modal-footer justify-content-between" style="border-top:1px solid var(--card-border);">
                <a href="#" id="btnViewUserFullPage" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Profile
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-accent d-none" id="btnViewUserToEdit">
                        <i class="bi bi-pencil me-1"></i> Edit User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("users.data") }}',
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'role', name: 'role' },
            @if(auth()->user()->isOwner())
            { data: 'shop', name: 'shop', orderable: false, searchable: false },
            @endif
            { data: 'admin_pos', name: 'allow_pos_admin', orderable: false, searchable: false, className: 'text-center' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });

    $(document).on('change', '.pos-admin-toggle', function() {
        const toggle = $(this);
        const url = toggle.data('url');
        const isChecked = toggle.is(':checked');

        toggle.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'PATCH'
            },
            success: function(res) {
                toggle.prop('disabled', false);
                toggle.prop('checked', res.allow_pos_admin);
                
                const title = res.allow_pos_admin 
                    ? 'Admin POS Enabled (Click to disable)' 
                    : 'Admin POS Disabled (Click to enable)';
                toggle.attr('title', title);

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.message || 'Updated successfully',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                }
            },
            error: function(xhr) {
                toggle.prop('disabled', false);
                toggle.prop('checked', !isChecked);
                const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update Admin POS setting.';
                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: err,
                        showConfirmButton: false,
                        timer: 3500
                    });
                } else {
                    alert(err);
                }
            }
        });
    });

    function updateModalUserRoleVisibility() {
        const $role = $('#modalRoleSelect');
        if (!$role.length) return;

        const role = $role.val();
        const isShopAdmin = role === 'shop_admin';
        const isSystemAdmin = role === 'system_admin';
        const $stockWrap = $('#modalAllowStockWrapper');
        const $posWrap = $('#modalAllowPosWrapper');
        const $shopSelect = $('#modalShopSelect');
        const $sysNote = $('.modal-system-admin-note');

        if (isSystemAdmin) {
            $stockWrap.hide();
            $posWrap.hide();
            $('#modalAllowPosAdmin').prop('checked', false);
            $('#modalAllowStockAddition').prop('checked', false);
            $shopSelect.val('').prop('disabled', true);
            $sysNote.removeClass('d-none');
        } else if (isShopAdmin) {
            $stockWrap.show();
            $posWrap.hide();
            $('#modalAllowPosAdmin').prop('checked', false);
            $shopSelect.prop('disabled', false);
            $sysNote.addClass('d-none');
        } else {
            $stockWrap.hide();
            $posWrap.show();
            $('#modalAllowStockAddition').prop('checked', false);
            $shopSelect.prop('disabled', false);
            $sysNote.addClass('d-none');
        }
    }

    let lastViewedUser = null;

    function escUser(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function openUserEditModal(data) {
        $('#userModalTitleText').text('Edit User');
        $('#btnSaveUserText').text('Update User');
        $('#userFormMethod').val('PUT');
        $('#userFormModalTag').val('edit_user');
        $('#userFormActionMirror').val(data.updateUrl);
        $('#addUserForm').attr('action', data.updateUrl);

        $('#userInputName').val(data.name || '');
        $('#userInputEmail').val(data.email || '');
        $('#userInputPhone').val(data.phone || '');
        if ($('#modalRoleSelect').length) {
            $('#modalRoleSelect').val(data.role || 'seller');
        }
        if ($('#modalShopSelect').length) {
            $('#modalShopSelect').val(data.shopId || '');
        }
        $('#modalAllowStockAddition').prop('checked', !!data.allowStock);
        $('#modalAllowPosAdmin').prop('checked', !!data.allowPos);

        $('#modalStatusWrapper').removeClass('d-none');
        $('#modalUserStatus').prop('disabled', false).val(data.status || 'active');

        $('#modalDefaultPwdNotice').addClass('d-none');
        $('#modalHiddenPwd, #modalHiddenPwdConfirm').prop('disabled', true).prop('required', false);

        updateModalUserRoleVisibility();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addUserModal')).show();
    }

    $('#modalRoleSelect').on('change', updateModalUserRoleVisibility);
    updateModalUserRoleVisibility();

    $('#btnNewUser').on('click', function() {
        const form = document.getElementById('addUserForm');
        if (form) form.reset();
        $('#userModalTitleText').text('{{ auth()->user()->isShopAdmin() ? "Add Seller — " . e(auth()->user()->shop?->shop_name) : "Register New User" }}');
        $('#btnSaveUserText').text('{{ auth()->user()->isShopAdmin() ? "Add Seller" : "Register User" }}');
        $('#userFormMethod').val('POST');
        $('#userFormModalTag').val('add_user');
        $('#userFormActionMirror').val('');
        $('#addUserForm').attr('action', '{{ route("users.store") }}');
        $('#userInputName, #userInputEmail, #userInputPhone').val('');
        if ($('#modalRoleSelect').length) $('#modalRoleSelect').val('seller');
        if ($('#modalShopSelect').length) $('#modalShopSelect').val('');
        $('#modalAllowStockAddition, #modalAllowPosAdmin').prop('checked', false);

        $('#modalStatusWrapper').addClass('d-none');
        $('#modalUserStatus').prop('disabled', true);

        $('#modalDefaultPwdNotice').removeClass('d-none');
        $('#modalHiddenPwd, #modalHiddenPwdConfirm').prop('disabled', false).prop('required', true).val('password');

        updateModalUserRoleVisibility();
    });

    $(document).on('click', '.btn-edit-user', function() {
        const btn = $(this);
        openUserEditModal({
            name: btn.data('name'),
            email: btn.data('email'),
            phone: btn.data('phone'),
            role: btn.data('role'),
            shopId: btn.data('shop-id'),
            status: btn.data('status'),
            allowStock: btn.data('allow-stock') == 1,
            allowPos: btn.data('allow-pos') == 1,
            updateUrl: btn.data('update-url')
        });
    });

    $(document).on('click', '.btn-view-user', function() {
        const url = $(this).data('show-url');
        $('#viewUserTitle').text('User Details');
        $('#viewUserBody').html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading user details...</div>');
        $('#btnViewUserFullPage').attr('href', url);
        $('#btnViewUserToEdit').addClass('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewUserModal')).show();

        $.getJSON(url).done(function(res) {
            lastViewedUser = {
                name: res.name,
                email: res.email,
                phone: res.phone,
                role: res.role,
                shopId: res.shop_id,
                status: res.status,
                allowStock: res.allow_stock_addition,
                allowPos: res.allow_pos_admin,
                updateUrl: res.update_url
            };
            $('#viewUserTitle').text(res.name);
            $('#btnViewUserToEdit').toggleClass('d-none', !res.can_edit);

            const statusBadge = res.status === 'active'
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>'
                : '<span class="badge bg-secondary-subtle text-secondary border">Disabled</span>';

            const perms = [];
            if (res.role === 'seller') {
                perms.push(res.allow_pos_admin ? 'Admin POS: Enabled' : 'Admin POS: Disabled');
            } else if (res.role === 'shop_admin') {
                perms.push(res.allow_stock_addition ? 'Owner Stock Addition: Allowed' : 'Owner Stock Addition: Restricted');
            } else {
                perms.push('Global Full Access');
            }

            $('#viewUserBody').html(`
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                    <div>
                        <h6 class="mb-1 fw-700">${escUser(res.name)}</h6>
                        <span class="role-pill role-${escUser(res.role)}">${escUser(res.role_label)}</span>
                        <span class="ms-1">${statusBadge}</span>
                    </div>
                    <div class="text-end small text-muted">Joined ${escUser(res.created_at)}</div>
                </div>
                <table class="table table-borderless table-sm mb-3" style="font-size:.83rem;">
                    <tr><th class="text-muted" style="width:36%;">Email</th><td>${escUser(res.email)}</td></tr>
                    <tr><th class="text-muted">Phone</th><td>${escUser(res.phone || '—')}</td></tr>
                    <tr><th class="text-muted">Assigned Shop</th><td class="fw-600">${escUser(res.shop_name)}</td></tr>
                    <tr><th class="text-muted">Permissions</th><td>${escUser(perms.join(' · '))}</td></tr>
                </table>
                <div class="row g-2">
                    <div class="col-6"><div class="p-2 rounded border bg-light"><div class="text-muted" style="font-size:.68rem;">RECORDED SALES</div><div class="fw-800 text-success" style="font-size:.88rem;">TZS ${Number(res.sales_total).toLocaleString()}</div><div class="text-muted" style="font-size:.68rem;">${res.sales_count} sale(s)</div></div></div>
                    <div class="col-6"><div class="p-2 rounded border bg-light"><div class="text-muted" style="font-size:.68rem;">DEFECTS LOGGED</div><div class="fw-800 text-primary" style="font-size:.88rem;">${res.defects_count}</div><div class="text-muted" style="font-size:.68rem;">reported record(s)</div></div></div>
                </div>
            `);
        }).fail(function() {
            $('#viewUserBody').html('<div class="alert alert-danger mb-0">Failed to load user details.</div>');
        });
    });

    $('#btnViewUserToEdit').on('click', function() {
        if (!lastViewedUser) return;
        bootstrap.Modal.getInstance(document.getElementById('viewUserModal'))?.hide();
        openUserEditModal(lastViewedUser);
    });

    $('#addUserForm').on('submit', function() {
        $('#modalShopSelect').prop('disabled', false);
    });

    @if($userModalErr)
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addUserModal')).show();
    @endif
});
</script>
@endpush
