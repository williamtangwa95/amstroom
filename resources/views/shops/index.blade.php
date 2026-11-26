@extends('layouts.app')
@section('title', 'Shops')
@section('page-title', 'Shop Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Shops</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h5 class="mb-0 fw-700">@if(auth()->user()->isOwner()) All Shops @else My Shop Profile @endif</h5>
        <small style="color:var(--text-secondary);">@if(auth()->user()->isOwner()) Manage your retail branches @else View and manage your shop profile details @endif</small>
    </div>
    @if(auth()->user()->isOwner())
    <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addShopModal" id="btnNewShop">
        <i class="bi bi-plus-circle me-1"></i> Add Shop
    </button>
    @endif
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="shopsTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Shop Name</th>
                    <th data-priority="3" class="min-tablet-l">Location</th>
                    <th data-priority="3" class="min-tablet-l">Phone</th>
                    <th data-priority="2" class="min-tablet-l">Users</th>
                    <th data-priority="2" class="min-tablet-l">Sales</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Shop Modal -->
@php
    $shopModalErr = $errors->any() && in_array(old('_modal'), ['add_shop', 'edit_shop'], true);
    $shopModalIsEdit = $shopModalErr && old('_modal') === 'edit_shop';
@endphp
<div class="modal fade" id="addShopModal" tabindex="-1" aria-labelledby="addShopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addShopModalLabel">
                    <i class="bi bi-shop me-2" style="color:var(--accent-blue);"></i>
                    <span id="shopModalTitleText">{{ $shopModalIsEdit ? 'Edit Shop' : 'New Shop' }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addShopForm" method="POST" action="{{ $shopModalIsEdit ? old('_action', route('shops.store')) : route('shops.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="shopFormMethod" value="{{ $shopModalIsEdit ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_modal" id="shopFormModalTag" value="{{ $shopModalIsEdit ? 'edit_shop' : 'add_shop' }}">
                <input type="hidden" name="_action" id="shopFormActionMirror" value="{{ $shopModalIsEdit ? old('_action') : '' }}">
                <div class="modal-body">
                    @if($shopModalErr)
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
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Shop Name <span class="text-danger">*</span></label>
                            <input type="text" name="shop_name" id="shopInputName" class="form-control" value="{{ $shopModalErr ? old('shop_name') : '' }}" placeholder="e.g. AmstRoom City Branch" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Slogan / Tagline (Optional)</label>
                            <input type="text" name="slogan" id="shopInputSlogan" class="form-control" value="{{ $shopModalErr ? old('slogan') : '' }}" placeholder="e.g. City Centre Branch - Innovations">
                            <small class="text-muted" style="font-size:.72rem;">If left empty, main owner slogan will be used for this shop.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Shop Logo (Optional)</label>
                            <div id="editShopCurrentLogoWrap" class="mb-2 d-none">
                                <img id="editShopCurrentLogo" src="" alt="Shop Logo" class="rounded border p-1 bg-light" style="max-height:55px;object-fit:contain;">
                            </div>
                            <input type="file" name="logo" id="shopInputLogo" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif">
                            <small class="text-muted" style="font-size:.72rem;">If left empty, main owner logo will be used (Max {{ \App\Models\Setting::get('max_upload_size_mb', 5) }}MB).</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Location <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="shopInputLocation" class="form-control" value="{{ $shopModalErr ? old('location') : '' }}" placeholder="e.g. Dar es Salaam, Tanzania" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Phone</label>
                            <input type="text" name="phone" id="shopInputPhone" class="form-control" value="{{ $shopModalErr ? old('phone') : '' }}" placeholder="+255...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Email</label>
                            <input type="email" name="email" id="shopInputEmail" class="form-control" value="{{ $shopModalErr ? old('email') : '' }}" placeholder="shop@amstroom.com">
                        </div>
                        @if(auth()->user()->isOwner())
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Status <span class="text-danger">*</span></label>
                            <select name="status" id="shopInputStatus" class="form-select" required>
                                <option value="active" {{ ($shopModalErr ? old('status', 'active') : 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $shopModalErr && old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">
                        <i class="bi bi-save me-1"></i> <span id="btnSaveShopText">{{ $shopModalIsEdit ? 'Update Shop' : 'Save Shop' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Shop Modal -->
<div class="modal fade" id="viewShopModal" tabindex="-1" aria-labelledby="viewShopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="viewShopModalLabel">
                    <i class="bi bi-shop me-2" style="color:var(--accent-blue);"></i>
                    <span id="viewShopTitle">Shop Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewShopBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading shop details...</div>
            </div>
            <div class="modal-footer justify-content-between" style="border-top:1px solid var(--card-border);">
                <a href="#" id="btnViewShopFullPage" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Profile
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-accent" id="btnViewShopToEdit">
                        <i class="bi bi-pencil me-1"></i> Edit Shop
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
    $('#shopsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("shops.data") }}',
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'shop_name', name: 'shop_name' },
            { data: 'location', name: 'location' },
            { data: 'phone', name: 'phone' },
            { data: 'employees', name: 'employees', orderable: false, searchable: false },
            { data: 'sales', name: 'sales', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });

    let lastViewedShop = null;

    function escShop(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function openShopEditModal(data) {
        $('#shopModalTitleText').text('Edit Shop');
        $('#btnSaveShopText').text('Update Shop');
        $('#shopFormMethod').val('PUT');
        $('#shopFormModalTag').val('edit_shop');
        $('#shopFormActionMirror').val(data.updateUrl);
        $('#addShopForm').attr('action', data.updateUrl);

        $('#shopInputName').val(data.name || '');
        $('#shopInputSlogan').val(data.slogan || '');
        $('#shopInputLocation').val(data.location || '');
        $('#shopInputPhone').val(data.phone || '');
        $('#shopInputEmail').val(data.email || '');
        $('#shopInputLogo').val('');
        if ($('#shopInputStatus').length) {
            $('#shopInputStatus').val(data.status || 'active');
        }
        if (data.logoUrl) {
            $('#editShopCurrentLogo').attr('src', data.logoUrl);
            $('#editShopCurrentLogoWrap').removeClass('d-none');
        } else {
            $('#editShopCurrentLogoWrap').addClass('d-none');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('addShopModal')).show();
    }

    $('#btnNewShop').on('click', function() {
        const form = document.getElementById('addShopForm');
        if (form) form.reset();
        $('#shopModalTitleText').text('New Shop');
        $('#btnSaveShopText').text('Save Shop');
        $('#shopFormMethod').val('POST');
        $('#shopFormModalTag').val('add_shop');
        $('#shopFormActionMirror').val('');
        $('#addShopForm').attr('action', '{{ route("shops.store") }}');
        $('#shopInputName, #shopInputSlogan, #shopInputLocation, #shopInputPhone, #shopInputEmail, #shopInputLogo').val('');
        if ($('#shopInputStatus').length) $('#shopInputStatus').val('active');
        $('#editShopCurrentLogoWrap').addClass('d-none');
    });

    $(document).on('click', '.btn-edit-shop', function() {
        const btn = $(this);
        openShopEditModal({
            name: btn.data('name'),
            slogan: btn.data('slogan'),
            location: btn.data('location'),
            phone: btn.data('phone'),
            email: btn.data('email'),
            status: btn.data('status'),
            logoUrl: btn.data('logo-url'),
            updateUrl: btn.data('update-url')
        });
    });

    $(document).on('click', '.btn-view-shop', function() {
        const url = $(this).data('show-url');
        $('#viewShopTitle').text('Shop Details');
        $('#viewShopBody').html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading shop details...</div>');
        $('#btnViewShopFullPage').attr('href', url);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewShopModal')).show();

        $.getJSON(url).done(function(res) {
            lastViewedShop = {
                name: res.shop_name,
                slogan: res.slogan,
                location: res.location,
                phone: res.phone,
                email: res.email,
                status: res.status,
                logoUrl: res.logo_url,
                updateUrl: res.update_url
            };
            $('#viewShopTitle').text(res.shop_name);

            const logoHtml = res.logo_url
                ? `<img src="${escShop(res.logo_url)}" alt="${escShop(res.shop_name)}" class="rounded-circle border bg-white p-1" style="width:52px;height:52px;object-fit:contain;">`
                : `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-700" style="width:52px;height:52px;font-size:1.1rem;">${escShop((res.shop_name || 'SH').substring(0, 2).toUpperCase())}</div>`;

            const statusBadge = res.status === 'active'
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>'
                : '<span class="badge bg-secondary-subtle text-secondary border">Inactive</span>';

            let stockRows = '';
            if (res.stocks && res.stocks.length) {
                res.stocks.forEach(function(st) {
                    stockRows += `<tr>
                        <td class="fw-600">${escShop(st.item_name)}</td>
                        <td class="text-center"><span class="badge ${st.is_low ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'}">${st.remaining_quantity} ${escShop(st.unit_symbol)}</span></td>
                        <td class="text-end">TZS ${Number(st.selling_price).toLocaleString()}</td>
                    </tr>`;
                });
            } else {
                stockRows = '<tr><td colspan="3" class="text-center text-muted py-2">No stock items in this branch yet</td></tr>';
            }

            let empRows = '';
            if (res.employees && res.employees.length) {
                res.employees.forEach(function(emp) {
                    empRows += `<div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size:.8rem;">
                        <div><span class="fw-600">${escShop(emp.name)}</span> <span class="text-muted">(${escShop(emp.email)})</span></div>
                        <span class="badge bg-light text-dark border">${escShop(emp.role)}</span>
                    </div>`;
                });
            } else {
                empRows = '<div class="text-muted small">No users assigned yet.</div>';
            }

            $('#viewShopBody').html(`
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom flex-wrap">
                    ${logoHtml}
                    <div class="flex-grow-1" style="min-width:180px;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h6 class="mb-0 fw-700">${escShop(res.shop_name)}</h6>
                            ${statusBadge}
                        </div>
                        <div class="small text-muted">${escShop(res.slogan || res.location || 'Retail Branch')}</div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-geo-alt me-1"></i>${escShop(res.location || '—')}
                            &middot; <i class="bi bi-telephone ms-1 me-1"></i>${escShop(res.phone || '—')}
                            &middot; <i class="bi bi-envelope ms-1 me-1"></i>${escShop(res.email || '—')}
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-sm-3"><div class="p-2 rounded border bg-light"><div class="text-muted" style="font-size:.68rem;">SALES REVENUE</div><div class="fw-800 text-success" style="font-size:.86rem;">TZS ${Number(res.sales_total).toLocaleString()}</div><div class="text-muted" style="font-size:.68rem;">${res.sales_count} sales</div></div></div>
                    <div class="col-6 col-sm-3"><div class="p-2 rounded border bg-light"><div class="text-muted" style="font-size:.68rem;">UNITS IN STOCK</div><div class="fw-800 text-primary" style="font-size:.86rem;">${Number(res.stock_count).toLocaleString()}</div><div class="text-muted" style="font-size:.68rem;">${res.total_products} products</div></div></div>
                    <div class="col-6 col-sm-3"><div class="p-2 rounded border bg-light"><div class="text-muted" style="font-size:.68rem;">BRANCH TEAM</div><div class="fw-800" style="font-size:.86rem;color:#4f46e5;">${(res.employees || []).length}</div><div class="text-muted" style="font-size:.68rem;">assigned staff</div></div></div>
                    <div class="col-6 col-sm-3"><div class="p-2 rounded border bg-light"><div class="text-muted" style="font-size:.68rem;">LOW ALERTS</div><div class="fw-800 ${res.low_stock_count > 0 ? 'text-danger' : 'text-success'}" style="font-size:.86rem;">${res.low_stock_count}</div><div class="text-muted" style="font-size:.68rem;">${res.low_stock_count > 0 ? 'needs attention' : 'healthy'}</div></div></div>
                </div>
                <div class="fw-700 small text-uppercase text-muted mb-2"><i class="bi bi-layers me-1 text-primary"></i>Top Shop Inventory</div>
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm mb-0" style="font-size:.79rem;">
                        <thead class="table-light"><tr><th>Product</th><th class="text-center">Remaining</th><th class="text-end">Price</th></tr></thead>
                        <tbody>${stockRows}</tbody>
                    </table>
                </div>
                <div class="fw-700 small text-uppercase text-muted mb-1"><i class="bi bi-people me-1 text-indigo"></i>Branch Team</div>
                <div>${empRows}</div>
            `);
        }).fail(function() {
            $('#viewShopBody').html('<div class="alert alert-danger mb-0">Failed to load shop details.</div>');
        });
    });

    $('#btnViewShopToEdit').on('click', function() {
        if (!lastViewedShop) return;
        bootstrap.Modal.getInstance(document.getElementById('viewShopModal'))?.hide();
        openShopEditModal(lastViewedShop);
    });

    @if($shopModalErr)
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addShopModal')).show();
    @endif
});
</script>
@endpush
