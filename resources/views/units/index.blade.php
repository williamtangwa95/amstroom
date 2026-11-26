@extends('layouts.app')
@section('title', 'Measurement Units')
@section('page-title', 'Measurement Units')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('items.index') }}">Products</a></li>
<li class="breadcrumb-item active">Units</li>
@endsection

@push('styles')
<style>
    /* ── Units Executive KPI Cards (Matches /diary) ── */
    .unit-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.25rem;
    }

    .unit-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .unit-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }

    .unit-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .unit-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .unit-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .unit-kpi-val {
        font-size: clamp(1rem, 1.4vw, 1.28rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .unit-kpi-sub {
        font-size: .71rem;
        color: #94a3b8;
        margin-top: .2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .unit-example-list {
        display: flex;
        flex-direction: column;
        gap: .26rem;
        margin-top: .12rem;
    }
    .unit-example-item {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .73rem;
        color: var(--text-secondary, #64748b);
        min-width: 0;
    }
    .unit-example-item span:last-child {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .unit-example-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        padding: .1rem .38rem;
        border-radius: 6px;
        background: rgba(0, 119, 182, .1);
        color: #0077b6;
        font-size: .68rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    @media (max-width: 991.98px) {
        .unit-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .unit-kpi-examples {
            grid-column: 1 / -1;
        }
    }
    @media (max-width: 575.98px) {
        .unit-kpi-grid {
            grid-template-columns: 1fr;
            gap: .65rem;
        }
        .unit-kpi-card {
            padding: .85rem .9rem;
            gap: .7rem;
            border-radius: 12px;
        }
        .unit-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-700">Stock &amp; Sales Units</h5>
        <small style="color:var(--text-secondary);">Manage measurement units used across catalog, warehouse stock, and POS</small>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-box-seam me-1"></i> Products Catalog
        </a>
        <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#unitModal" id="btn-new-unit">
            <i class="bi bi-plus-circle me-1"></i> New Unit
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="unit-kpi-grid">
    <div class="unit-kpi-card">
        <div class="unit-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-rulers"></i>
        </div>
        <div class="unit-kpi-body">
            <p class="unit-kpi-label">Total Units</p>
            <div class="unit-kpi-val" id="cardTotalUnits" style="color:#0077b6;">{{ number_format($totalUnits) }}</div>
            <div class="unit-kpi-sub">Registered Measurement Units</div>
        </div>
    </div>

    <div class="unit-kpi-card">
        <div class="unit-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="unit-kpi-body">
            <p class="unit-kpi-label">Default Unit</p>
            <div class="unit-kpi-val" style="color:#059669;">{{ $defaultUnit ? $defaultUnit->name . ' (' . $defaultUnit->symbol . ')' : 'None' }}</div>
            <div class="unit-kpi-sub">Applied to items without explicit unit</div>
        </div>
    </div>

    <div class="unit-kpi-card unit-kpi-examples">
        <div class="unit-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-lightbulb-fill"></i>
        </div>
        <div class="unit-kpi-body">
            <p class="unit-kpi-label">Usage Examples</p>
            <div class="unit-example-list">
                <div class="unit-example-item">
                    <span class="unit-example-badge">pc</span>
                    <span>Dell Latitude (3 pc)</span>
                </div>
                <div class="unit-example-item">
                    <span class="unit-example-badge">m</span>
                    <span>CAT 6 UTP Cable (305 m)</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DataTable Card --}}
<div class="card border-0 shadow-sm" style="background:var(--card-bg);border:1px solid var(--card-border) !important;border-radius:12px;">
    <div class="card-body p-0 p-md-3">
        <table id="unitsTable" class="table table-hover align-middle w-100 mb-0 dt-mobile-table" style="font-size:0.85rem;">
            <thead>
                <tr style="color:var(--text-secondary);font-size:0.75rem;text-transform:uppercase;">
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Unit Name</th>
                    <th data-priority="2" class="min-tablet-l">Symbol / Code</th>
                    <th data-priority="3" class="min-tablet-l">Description</th>
                    <th data-priority="2" class="min-tablet-l">Assigned Products</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Add / Edit Modal --}}
<div class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="unitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="unitModalLabel">
                    <i class="bi bi-rulers me-2" style="color:var(--accent-blue);"></i>
                    <span id="modalTitleText">New Measurement Unit</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="unitForm" method="POST" action="{{ route('units.store') }}">
                @csrf
                <input type="hidden" id="unitMethod" name="_method" value="POST">
                <input type="hidden" id="unitId" name="unit_id" value="">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">
                                Unit Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="inputUnitName" name="name"
                                placeholder="e.g. Piece, Meter, Box, Roll, Kilogram"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);"
                                required maxlength="100">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">
                                Symbol / Short Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="inputUnitSymbol" name="symbol"
                                placeholder="e.g. pc, m, box, roll, kg"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);"
                                required maxlength="20">
                            <small class="text-muted" style="font-size:0.72rem;">This symbol will be displayed in POS, inventory, receipts, and stock tables.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">
                                Description
                            </label>
                            <input type="text" class="form-control" id="inputUnitDescription" name="description"
                                placeholder="e.g. Length in meters, packaged box"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);"
                                maxlength="255">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="inputIsDefault" name="is_default" value="1" style="width:2.2em;height:1.2em;">
                                <label class="form-check-label ms-2 small fw-600" for="inputIsDefault" style="color:var(--text-primary);">
                                    Set as default measurement unit
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent" id="btnSaveUnit">
                        <i class="bi bi-save me-1"></i> Save Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#unitsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("units.data") }}'
        },
        columns: [
            { data: 'id',          orderable: false, responsivePriority: 1, className: 'all iter-col text-center' },
            { data: 'name',        orderable: true,  responsivePriority: 1, className: 'all primary-col' },
            { data: 'symbol',      orderable: true,  responsivePriority: 2, className: 'min-tablet-l' },
            { data: 'description', orderable: false, responsivePriority: 3, className: 'min-tablet-l' },
            { data: 'items_count', orderable: true,  responsivePriority: 2, className: 'min-tablet-l' },
            { data: 'actions',     orderable: false, responsivePriority: 3, className: 'min-tablet-l text-end' }
        ],
        order: [[1, 'asc']],
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, 'All']],
        language: {
            search: '',
            searchPlaceholder: 'Search units…',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
            emptyTable: '<div class="text-center py-4" style="color:var(--text-secondary);"><i class="bi bi-rulers fs-4 d-block mb-2"></i>No units found</div>',
        },
        dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"lf>rtip',
    });

    $('#btn-new-unit').on('click', function () {
        resetModal();
    });

    $(document).on('click', '.btn-edit-unit', function () {
        const btn = $(this);
        const id = btn.data('id');

        $('#modalTitleText').text('Edit Measurement Unit');
        $('#unitMethod').val('PUT');
        $('#unitId').val(id);
        $('#unitForm').attr('action', '/units/' + id);

        $('#inputUnitName').val(btn.data('name'));
        $('#inputUnitSymbol').val(btn.data('symbol'));
        $('#inputUnitDescription').val(btn.data('description'));
        $('#inputIsDefault').prop('checked', btn.data('is-default') == 1);

        $('#unitModal').modal('show');
    });

    function resetModal() {
        $('#modalTitleText').text('New Measurement Unit');
        $('#unitMethod').val('POST');
        $('#unitId').val('');
        $('#unitForm').attr('action', '{{ route("units.store") }}');
        $('#unitForm')[0].reset();
        $('#inputIsDefault').prop('checked', false);
    }

    $(document).on('click', '.btn-delete-unit', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const itemsCount = parseInt($(this).data('items-count')) || 0;

        if (itemsCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Delete Unit',
                text: `The unit "${name}" is currently assigned to ${itemsCount} product(s). Please reassign those products before deleting this unit.`,
                background: '#161b22',
                color: '#e6edf3'
            });
            return;
        }

        Swal.fire({
            title: 'Delete Unit?',
            html: `<p style="color:var(--text-secondary);">Are you sure you want to delete unit <strong style="color:var(--text-primary);">${name}</strong>?<br>This action cannot be undone.</p>`,
            icon: 'warning',
            background: '#161b22',
            color: '#e6edf3',
            showCancelButton: true,
            confirmButtonColor: '#e94560',
            cancelButtonColor: '#30363d',
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/units/' + id,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        if (res.success) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Measurement unit deleted.',
                                background: '#161b22',
                                color: '#e6edf3',
                                timer: 1800,
                                showConfirmButton: false,
                            });
                        }
                    },
                    error: function (xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not delete unit.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg, background: '#161b22', color: '#e6edf3' });
                    }
                });
            }
        });
    });

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session("success") }}',
            background: '#161b22',
            color: '#e6edf3',
            timer: 2500,
            showConfirmButton: false,
        });
    @endif
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session("error") }}',
            background: '#161b22',
            color: '#e6edf3',
        });
    @endif
});
</script>
@endpush
