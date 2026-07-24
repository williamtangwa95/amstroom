@extends('layouts.app')
@section('title', 'New Stock Request')
@section('page-title', 'Request Warehouse Stock')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Stock Requests</a></li>
<li class="breadcrumb-item active">New Request</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-cart-plus me-2" style="color:#e94560;"></i>Create Stock Request for {{ $shop->shop_name }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('stock-requests.store') }}">
                    @csrf
                    <div id="requestItemsContainer">
                        <div class="row g-2 mb-3 request-item-row align-items-end">
                            <div class="col-md-7">
                                <label class="form-label">Item *</label>
                                <select name="items[0][item_id]" class="form-select item-select" required>
                                    <option value="">Select product...</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-stock="{{ $item->getTotalMainStock() }}">[{{ $item->category->category_name }}] {{ $item->item_name }} (Warehouse Stock: {{ $item->getTotalMainStock() }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity *</label>
                                <input type="number" name="items[0][quantity]" class="form-control quantity-input" min="1" value="1" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100 remove-row" style="display:none;"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-custom mb-3" id="addItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Another Item
                    </button>

                    <div class="mb-3">
                        <label class="form-label">Notes / Reasons</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Urgent stock replenishment..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-send me-1"></i> Submit Request</button>
                        <a href="{{ route('stock-requests.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let itemIndex = 1;
document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('requestItemsContainer');
    const firstRow = container.querySelector('.request-item-row');
    const newRow = firstRow.cloneNode(true);

    newRow.querySelector('select').name = `items[${itemIndex}][item_id]`;
    newRow.querySelector('select').value = '';
    newRow.querySelector('input').name = `items[${itemIndex}][quantity]`;
    newRow.querySelector('input').value = '1';
    newRow.querySelector('input').removeAttribute('max');
    
    const removeBtn = newRow.querySelector('.remove-row');
    removeBtn.style.display = 'block';
    removeBtn.addEventListener('click', () => newRow.remove());

    container.appendChild(newRow);
    itemIndex++;
});

// Event delegation for validation
const container = document.getElementById('requestItemsContainer');

container.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-select')) {
        validateRowStock(e.target.closest('.request-item-row'));
    }
});

container.addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity-input')) {
        const row = e.target.closest('.request-item-row');
        const select = row.querySelector('.item-select');
        const option = select.options[select.selectedIndex];
        if (option && option.value !== '') {
            const stock = parseInt(option.getAttribute('data-stock') || 0, 10);
            let val = parseInt(e.target.value, 10);
            if (val > stock) {
                e.target.value = stock;
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Exceeded',
                    text: `The requested quantity cannot exceed the warehouse stock of ${stock}.`,
                    confirmButtonColor: '#0088cc'
                });
            }
        }
    }
});

function validateRowStock(row) {
    const select = row.querySelector('.item-select');
    const input = row.querySelector('.quantity-input');
    const option = select.options[select.selectedIndex];
    
    if (!option || option.value === '') {
        input.removeAttribute('max');
        return;
    }
    
    const stock = parseInt(option.getAttribute('data-stock') || 0, 10);
    input.setAttribute('max', stock);
    
    if (parseInt(input.value, 10) > stock) {
        input.value = stock;
    }
}
</script>
@endpush
