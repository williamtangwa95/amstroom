@extends('layouts.app')
@section('title', 'Measurement Units')
@section('page-title', 'Measurement Units')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('items.index') }}">Products</a></li>
<li class="breadcrumb-item active">Units</li>
@endsection

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
<div class="row g-2 g-md-3 mb-4">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Total Units</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(88,166,255,0.15); color:#58a6ff;">
                    <i class="bi bi-rulers fs-6"></i>
                </div>
            </div>
            <div class="fw-800 fs-5 mb-0" id="cardTotalUnits" style="color:#58a6ff;">{{ number_format($totalUnits) }}</div>
            <div class="small text-muted" style="font-size:0.68rem;">Registered Measurement Units</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Default Unit</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(63,185,80,0.15); color:#3fb950;">
                    <i class="bi bi-check-circle-fill fs-6"></i>
                </div>
            </div>
            <div class="fw-800 fs-5 mb-0" style="color:#3fb950;">{{ $defaultUnit ? $defaultUnit->name . ' (' . $defaultUnit->symbol . ')' : 'None' }}</div>
            <div class="small text-muted" style="font-size:0.68rem;">Applied to items without explicit unit</div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-4">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Usage Examples</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(227,179,65,0.15); color:#e3b341;">
                    <i class="bi bi-lightbulb-fill fs-6"></i>
                </div>
            </div>
            <div class="small text-muted" style="font-size:0.78rem;">
                <span class="badge me-1" style="background:rgba(88,166,255,0.15);color:#58a6ff;">pc</span> e.g. Dell Latitude (3 pc)<br>
                <span class="badge me-1" style="background:rgba(88,166,255,0.15);color:#58a6ff;">m</span> e.g. CAT 6 UTP Cable (305 m)
            </div>
        </div>
    </div>
</div>

{{-- DataTable Card --}}
<div class="card border-0 shadow-sm" style="background:var(--card-bg);border:1px solid var(--card-border) !important;border-radius:12px;">
    <div class="card-body p-0 p-md-3">
        <div class="table-responsive">
            <table id="unitsTable" class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                <thead>
                    <tr style="color:var(--text-secondary);font-size:0.75rem;text-transform:uppercase;">
                        <th>#</th>
                        <th>Unit Name</th>
                        <th>Symbol / Code</th>
                        <th>Description</th>
                        <th>Assigned Products</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
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
            { data: 'id',          orderable: false, width: '40px' },
            { data: 'name',        orderable: true },
            { data: 'symbol',      orderable: true },
            { data: 'description', orderable: false },
            { data: 'items_count', orderable: true },
            { data: 'actions',     orderable: false, className: 'text-end' }
        ],
        order: [[1, 'asc']],
        pageLength: 15,
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
