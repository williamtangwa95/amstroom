@extends('layouts.app')
@section('title', 'Shop Inventory')
@section('page-title', 'Shop Stock')
@section('breadcrumb')
<li class="breadcrumb-item active">Shop Stock</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Shop Inventory</h5>
        <small style="color:var(--text-secondary);">Available products in retail shops</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if(auth()->user()->isShopAdmin())
        <button type="button" class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminStockModal">
            <i class="bi bi-plus-circle me-1"></i> Add Admin Stock
        </button>
        @endif
        @if($lowStockItems > 0)
        <div class="alert alert-warning py-1 px-3 mb-0" style="font-size:.8rem;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $lowStockItems }} item(s) low in stock!
        </div>
        @endif
    </div>
</div>

@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('shop-stock.index') }}" class="row align-items-center g-2">
            <div class="col-auto"><label class="form-label mb-0">Filter by Shop:</label></div>
            <div class="col-auto">
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3 px-3 py-2 rounded border d-none" id="bulkActionsBar" style="background: var(--card-bg) !important; border-color: var(--card-border) !important;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check2-square text-accent fs-5"></i>
        <span class="fw-600 small" id="selectedCountText" style="color:var(--text-primary);">0 items selected</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-xs btn-accent px-3 py-1" id="bulkEnableBtn" style="font-size: .75rem;">Enable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-danger px-3 py-1" id="bulkDisableBtn" style="font-size: .75rem;">Disable Custom Components</button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="shopStockTable">
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="checkAllStocks" style="cursor:pointer;"></th>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Initial Qty</th>
                    <th>Remaining Qty</th>
                    <th>Alert Threshold</th>
                    <th>Selling Price</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $st)
                <tr class="{{ $st->isLowStock() ? 'low-stock-row' : '' }}">
                    <td>
                        @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id))
                            <input type="checkbox" class="stock-checkbox" data-id="{{ $st->id }}" style="cursor:pointer;">
                        @else
                            <input type="checkbox" disabled style="cursor:not-allowed; opacity: 0.5;">
                        @endif
                    </td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $loop->iteration }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $st->shop->shop_name }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($st->item->image_path)
                            <img src="{{ asset('storage/' . $st->item->image_path) }}"
                                 alt="{{ $st->item->item_name }}"
                                 class="rounded img-lightbox"
                                 style="width: 32px; height: 32px; object-fit: cover; border: 1px solid var(--card-border);"
                                 onclick="openLightbox(this.src, '{{ addslashes($st->item->item_name) }}')"
                                 title="Click to enlarge">
                            @else
                            <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width: 32px; height: 32px; border: 1px solid var(--card-border);">
                                <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:.83rem;">{{ $st->item->item_name }}</div>
                                <div style="font-size:.7rem;color:var(--text-secondary);">
                                    {{ $st->item->brand }}
                                    @if($st->is_admin_stock)
                                    <span style="background:rgba(57,178,255,.12);color:#39b2ff;padding:.15rem .4rem;border-radius:6px;font-size:.65rem;font-weight:600;margin-left:5px;">Admin Stock</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $st->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">{{ $st->quantity }}</td>
                    <td>
                        <strong style="color:{{ $st->isLowStock() ? '#e94560' : '#3fb950' }};font-size:.9rem;">
                            {{ $st->remaining_quantity }}
                        </strong>
                        @if($st->isLowStock())
                        <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#e94560;font-size:.75rem;" title="Low Stock!"></i>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">{{ $st->low_stock_alert }} units</td>
                    <td style="font-size:.82rem;font-weight:600;">
                        @if(\App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT' && !$st->is_sellable)
                            <span class="text-danger">TZS {{ number_format($st->selling_price, 0) }}</span>
                            <div class="badge bg-danger mt-1 d-block text-wrap" style="font-size:.7rem;" title="Locked pending selling price update">
                                <i class="bi bi-lock-fill"></i> PENDING_PRICE_UPDATE
                            </div>
                            <div class="small mt-1 text-info" style="font-size:.7rem; font-weight:normal;">
                                New Transfer Cost: TZS {{ number_format($st->buying_price, 0) }}
                            </div>
                        @else
                            TZS {{ number_format($st->selling_price, 0) }}
                            @if($st->is_price_pending)
                            <div class="small mt-1 text-warning" title="Pending Owner Approval">
                                <i class="bi bi-hourglass-split"></i> Pending: <strong>TZS {{ number_format($st->pending_selling_price, 0) }}</strong>
                            </div>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($st->is_price_pending && (auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id)))
                            <button type="button" class="btn btn-xs btn-success btn-approve-price"
                                data-url="{{ route('shop-stock.approve-price', $st) }}"
                                data-pending-price="{{ $st->pending_selling_price ?? $st->buying_price }}"
                                data-buying-price="{{ $st->buying_price }}"
                                data-current-selling-price="{{ $st->selling_price }}"
                                data-item-name="{{ $st->item->item_name }}"
                                data-mode="{{ \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') }}"
                                title="Approve Price Change">
                                <i class="bi bi-check-lg"></i> {{ \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT' ? 'Update Price' : 'Approve' }}
                            </button>
                            @endif
                            <a href="{{ route('shop-stock.show', $st) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-eye"></i></a>
                            @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id))
                            <div class="form-check form-switch ms-1 mb-0 d-flex align-items-center">
                                <input class="form-check-input toggle-components-btn" type="checkbox" data-id="{{ $st->id }}" style="cursor:pointer; width: 30px; height: 16px;" 
                                    {{ $st->allow_components ? 'checked' : '' }} title="Toggle custom components capability">
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Approve Price Modal -->
<div class="modal fade" id="approvePriceModal" tabindex="-1" aria-labelledby="approvePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="approvePriceModalLabel"><i class="bi bi-check-circle-fill text-success me-2"></i>Approve Price Change</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvePriceForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p class="mb-3 text-secondary" style="font-size:0.88rem;" id="modalInstructions">
                        Assign a new selling price for <strong id="modalItemName" class="text-white"></strong>.
                        The owner proposed TZS <span id="modalProposedPrice" class="fw-bold text-success"></span>.
                    </p>
                    <div class="mb-3">
                        <label for="modalSellingPrice" class="form-label" style="font-size:0.8rem;">Selling Price (TZS)</label>
                        <input type="text" class="form-control" id="modalSellingPrice" required autocomplete="off">
                        <input type="hidden" id="modalSellingPriceHidden" name="selling_price">
                        <div id="modalBuyingPriceHelp" class="form-text text-muted" style="font-size:0.75rem;"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Save & Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(auth()->user()->isShopAdmin())
<!-- Add Admin Stock Modal -->
<div class="modal fade" id="addAdminStockModal" tabindex="-1" aria-labelledby="addAdminStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addAdminStockModalLabel"><i class="bi bi-plus-circle text-accent me-2"></i>Add Admin Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.store-admin-stock') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="createNewProductToggle" name="create_new_product" value="1">
                        <label class="form-check-label small fw-600" for="createNewProductToggle">Create a new product instead</label>
                    </div>

                    <div id="existingProductGroup">
                        <div class="mb-3">
                            <label for="item_id" class="form-label" style="font-size:0.8rem;">Select Product *</label>
                            <select name="item_id" id="item_id" class="form-select form-select-sm" required>
                                <option value="">-- Choose Product --</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->item_name }} ({{ $item->brand }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="newProductGroup" style="display: none;">
                        <div class="mb-3">
                            <label for="new_item_name" class="form-label" style="font-size:0.8rem;">Product Name *</label>
                            <input type="text" name="new_item_name" id="new_item_name" class="form-control form-control-sm" placeholder="e.g. Wireless Keyboard K120">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="createNewCategoryToggle" name="create_new_category" value="1">
                            <label class="form-check-label small fw-600" for="createNewCategoryToggle">Create a new category instead</label>
                        </div>

                        <div id="existingCategoryGroup" class="mb-3">
                            <label for="category_id" class="form-label" style="font-size:0.8rem;">Category *</label>
                            <select name="category_id" id="category_id" class="form-select form-select-sm">
                                <option value="">-- Choose Category --</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="newCategoryGroup" class="mb-3" style="display: none;">
                            <label for="new_category_name" class="form-label" style="font-size:0.8rem;">Category Name *</label>
                            <input type="text" name="new_category_name" id="new_category_name" class="form-control form-control-sm" placeholder="e.g. Gaming Gear">
                        </div>
                        <div class="mb-3">
                            <label for="brand" class="form-label" style="font-size:0.8rem;">Brand</label>
                            <input type="text" name="brand" id="brand" class="form-control form-control-sm" placeholder="e.g. Logitech">
                        </div>
                        <div class="mb-3">
                            <label for="model" class="form-label" style="font-size:0.8rem;">Model</label>
                            <input type="text" name="model" id="model" class="form-control form-control-sm" placeholder="e.g. K120">
                        </div>
                        <div class="mb-3">
                            <label for="specification" class="form-label" style="font-size:0.8rem;">Specification</label>
                            <input type="text" name="specification" id="specification" class="form-control form-control-sm" placeholder="e.g. USB connection, spill-resistant">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_display" class="form-label" style="font-size:0.8rem;">Quantity *</label>
                        <input type="text" id="quantity_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 10">
                        <input type="hidden" name="quantity" id="quantity">
                    </div>
                    <div class="mb-3">
                        <label for="buying_price_display" class="form-label" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                        <input type="text" id="buying_price_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 1,000">
                        <input type="hidden" name="buying_price" id="buying_price">
                    </div>
                    <div class="mb-3">
                        <label for="selling_price_display" class="form-label" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                        <input type="text" id="selling_price_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 1,500">
                        <input type="hidden" name="selling_price" id="selling_price">
                    </div>
                    <div class="mb-3">
                        <label for="date_received" class="form-label" style="font-size:0.8rem;">Date Received *</label>
                        <input type="date" name="date_received" id="date_received" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#shopStockTable').DataTable();

        function formatNumber(val) {
            let clean = val.replace(/,/g, '');
            if (isNaN(parseFloat(clean))) return '';
            let parts = clean.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        // Auto-comma formatter
        $('#modalSellingPrice').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;

            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }

            this.value = formatNumber(cleanVal);
            $('#modalSellingPriceHidden').val(cleanVal);

            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        @if(auth()->user()->isShopAdmin())
        // Auto-comma formatter for Add Admin Stock Modal
        $('#quantity_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9]/g, '');
            this.value = formatNumber(cleanVal);
            $('#quantity').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#buying_price_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $('#buying_price').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#selling_price_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $('#selling_price').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#addAdminStockModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#quantity').val('');
            $('#buying_price').val('');
            $('#selling_price').val('');
        });
        @endif

        $('.btn-approve-price').on('click', function() {
            const url = $(this).data('url');
            const pendingPrice = $(this).data('pending-price');
            const buyingPrice = $(this).data('buying-price');
            const currentSellingPrice = $(this).data('current-selling-price');
            const itemName = $(this).data('item-name');
            const mode = $(this).data('mode');

            $('#approvePriceForm').attr('action', url);
            $('#modalItemName').text(itemName);

            if (mode === 'INDEPENDENT') {
                $('#approvePriceModalLabel').html('<i class="bi bi-shield-lock-fill text-warning me-2"></i>Update Selling Price');
                $('#modalInstructions').html(
                    'Main Store updated the transfer price for <strong class="text-white">' + itemName + '</strong>.<br>' +
                    'New transfer cost (Buying Price): <strong class="text-success">TZS ' + parseFloat(buyingPrice).toLocaleString() + '</strong>.<br>' +
                    'Please update your Selling Price to restore sales eligibility.'
                );

                const preFill = Math.max(parseFloat(currentSellingPrice), parseFloat(buyingPrice));
                const formattedPrice = formatNumber(preFill.toString());
                $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
                $('#modalSellingPriceHidden').val(preFill);
            } else {
                $('#approvePriceModalLabel').html('<i class="bi bi-check-circle-fill text-success me-2"></i>Approve Price Change');
                $('#modalInstructions').html(
                    'Assign a new selling price for <strong class="text-white">' + itemName + '</strong>.<br>' +
                    'The owner proposed TZS <span class="fw-bold text-success">' + parseFloat(pendingPrice).toLocaleString() + '</span>.'
                );

                const formattedPrice = formatNumber(pendingPrice.toString());
                $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
                $('#modalSellingPriceHidden').val(pendingPrice);
            }

            $('#modalBuyingPriceHelp').text('Minimum required price (Buying Price): TZS ' + parseFloat(buyingPrice).toLocaleString());

            const modal = new bootstrap.Modal(document.getElementById('approvePriceModal'));
            modal.show();
        });

        // Form validation
        $('#approvePriceForm').on('submit', function(e) {
            const buyingPrice = parseFloat($('#modalSellingPrice').data('buying-price'));
            const enteredPrice = parseFloat($('#modalSellingPriceHidden').val());

            if (enteredPrice < buyingPrice) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Price Too Low',
                    text: 'Selling price cannot be less than the buying price TZS ' + buyingPrice.toLocaleString() + '.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });

        @if(auth()->user()->isShopAdmin())
        function updateCategoryFields() {
            const isNewProduct = $('#createNewProductToggle').is(':checked');
            const isNewCategory = $('#createNewCategoryToggle').is(':checked');

            if (isNewProduct) {
                if (isNewCategory) {
                    $('#existingCategoryGroup').hide();
                    $('#category_id').val('').prop('required', false);
                    $('#newCategoryGroup').show();
                    $('#new_category_name').prop('required', true);
                } else {
                    $('#newCategoryGroup').hide();
                    $('#new_category_name').val('').prop('required', false);
                    $('#existingCategoryGroup').show();
                    $('#category_id').prop('required', true);
                }
            } else {
                $('#category_id').prop('required', false);
                $('#new_category_name').prop('required', false);
            }
        }

        // Toggle new product inputs vs select product dropdown
        $('#createNewProductToggle').on('change', function() {
            if (this.checked) {
                $('#existingProductGroup').hide();
                $('#item_id').val('').prop('required', false);
                $('#newProductGroup').show();
                $('#new_item_name').prop('required', true);
                updateCategoryFields();
            } else {
                $('#newProductGroup').hide();
                $('#new_item_name').val('').prop('required', false);
                $('#existingProductGroup').show();
                $('#item_id').prop('required', true);
                updateCategoryFields();
            }
        });

        // Toggle new category input vs category select dropdown
        $('#createNewCategoryToggle').on('change', function() {
            updateCategoryFields();
        });

        // Add Admin Stock Form validation
        $('#addAdminStockModal form').on('submit', function(e) {
            const buyingPrice = parseFloat($('#buying_price').val()) || 0;
            const sellingPrice = parseFloat($('#selling_price').val()) || 0;

            if (sellingPrice < buyingPrice) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Price Too Low',
                    text: 'Selling price cannot be less than the buying price TZS ' + buyingPrice.toLocaleString() + '.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });
        // Toggle shop components POS visibility per product
        $('.toggle-components-btn').on('change', function() {
            const isChecked = $(this).is(':checked');
            const shopStockId = $(this).data('id');
            const self = $(this);
            
            $.post("{{ route('settings.toggle-components') }}", {
                _token: "{{ csrf_token() }}",
                shop_stock_id: shopStockId,
                enabled: isChecked ? 1 : 0
            })
            .done(function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: isChecked ? 'Manual components enabled for this product.' : 'Manual components disabled for this product.',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                }
            })
            .fail(function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update setting. Please try again.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
                self.prop('checked', !isChecked);
            });
        });

        function updateBulkActionsBar() {
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });
            
            const count = checkedIds.length;
            if (count > 0) {
                $('#selectedCountText').html(`<strong>${count}</strong> item(s) selected`);
                $('#bulkActionsBar').removeClass('d-none');
            } else {
                $('#bulkActionsBar').addClass('d-none');
            }
        }

        $('#checkAllStocks').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.stock-checkbox:not(:disabled)').prop('checked', isChecked);
            updateBulkActionsBar();
        });

        $(document).on('change', '.stock-checkbox', function() {
            updateBulkActionsBar();
            if (!$(this).is(':checked')) {
                $('#checkAllStocks').prop('checked', false);
            }
        });

        function sendBulkUpdate(enabled) {
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });
            
            if (checkedIds.length === 0) return;
            
            Swal.fire({
                title: 'Please wait...',
                html: 'Updating selected products components capability...',
                allowOutsideClick: false,
                background: '#161b22',
                color: '#e6edf3',
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.post("{{ route('settings.toggle-components') }}", {
                _token: "{{ csrf_token() }}",
                shop_stock_ids: checkedIds,
                enabled: enabled ? 1 : 0
            })
            .done(function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: 'Products updated successfully!',
                    timer: 1500,
                    showConfirmButton: false,
                    background: '#161b22',
                    color: '#e6edf3'
                }).then(() => {
                    location.reload();
                });
            })
            .fail(function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to perform bulk update. Please try again.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            });
        }

        $('#bulkEnableBtn').on('click', () => sendBulkUpdate(true));
        $('#bulkDisableBtn').on('click', () => sendBulkUpdate(false));
        @endif
    });
</script>
@endpush