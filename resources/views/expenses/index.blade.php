@extends('layouts.app')
@section('title', 'Expenses')
@section('page-title', 'Expenses Ledger')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        <a href="{{ route('expense-categories.index') }}" class="btn btn-sm btn-outline-custom me-2">
            <i class="bi bi-tags-fill me-1"></i> Categories
        </a>
        <button type="button" id="btn-bulk-approve" class="btn btn-sm btn-success d-none">
            <i class="bi bi-check-all me-1"></i> Approve Selected (<span id="selected-count">0</span>)
        </button>
        @endif
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-accent">
        <i class="bi bi-plus-circle me-1"></i> Record Expense
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><i class="bi bi-wallet2 me-2" style="color:#3fb950;"></i>Expenses Ledger</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="expensesTable">
            <thead>
                <tr>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th style="width: 40px;"><input type="checkbox" id="select-all-expenses" class="form-check-input"></th>
                    @endif
                    <th>No</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Recorded By</th>
                    <th>Approved By</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                <tr>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <td>
                        @if($expense->isPending())
                        <input type="checkbox" class="expense-checkbox form-check-input" value="{{ $expense->id }}">
                        @endif
                    </td>
                    @endif
                    <td>{{ $loop->iteration }}</td>
                    <td class="small">{{ $expense->activity_date->format('M d, Y') }}</td>
                    <td><span class="badge" style="background:rgba(188,140,255,.12);color:#bc8cff;">{{ $expense->category->name }}</span></td>
                    <td><strong>{{ $expense->activity }}</strong></td>
                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $expense->description }}">{{ $expense->description ?: '—' }}</td>
                    <td><strong class="text-dark">TZS {{ number_format($expense->amount, 0) }}</strong></td>
                    <td class="small">{{ $expense->recorder->name ?? '—' }}</td>
                    <td class="small">{{ $expense->approver->name ?? '—' }}</td>
                    <td>
                        @if($expense->isPending())
                        <span class="badge badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                        @elseif($expense->isApproved())
                        <span class="badge badge-approved"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                        @elseif($expense->isReviewRequested())
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Review Requested</span>
                        @elseif($expense->isEditable())
                        <span class="badge bg-info text-dark"><i class="bi bi-pencil-square me-1"></i>Editable</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            {{-- Admin actions --}}
                            @if(auth()->user()->isShopAdmin())
                            @if($expense->isPending())
                            <form method="POST" action="{{ route('expenses.approve', $expense) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-custom btn-success" title="Approve Expense"><i class="bi bi-check-lg"></i> </button>
                            </form>
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" onsubmit="return confirm('Delete this expense?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @elseif($expense->isApproved())
                            <form method="POST" action="{{ route('expenses.request-review', $expense) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-custom text-warning" title="Request Edit Review"><i class="bi bi-shield-exclamation"></i> Request Edit</button>
                            </form>
                            @elseif($expense->isEditable())
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-xs btn-accent" title="Edit"><i class="bi bi-pencil"></i> Edit</a>
                            @endif
                            @endif

                            {{-- Owner actions --}}
                            @if(auth()->user()->isOwner())
                            @if($expense->isReviewRequested())
                            <form method="POST" action="{{ route('expenses.grant-edit', $expense) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-custom btn-info text-dark" title="Grant Edit Ability"><i class="bi bi-unlock-fill"></i> Grant Edit</button>
                            </form>
                            @endif
                            @if($expense->isPending())
                            <form method="POST" action="{{ route('expenses.approve', $expense) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-custom btn-success" title="Approve Expense"><i class="bi bi-check-lg"></i> </button>
                            </form>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" onsubmit="return confirm('Delete this expense?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                            @if($expense->isApproved() || $expense->isReviewRequested() || $expense->isEditable())
                            <form method="POST" action="{{ route('expenses.revert-approval', $expense) }}" class="d-inline" onsubmit="return confirm('Revert approval for this expense? This will set its status to pending, allowing deletion.');">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-custom text-warning" title="Revert Approval"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>
                            </form>
                            @endif
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endif

                            {{-- Seller actions --}}
                            @if(auth()->user()->isSeller() && $expense->isPending())
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" onsubmit="return confirm('Delete this expense?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif

                            @if($expense->isApproved() && !auth()->user()->isShopAdmin() && !auth()->user()->isOwner())
                            <span class="text-muted small py-1 px-2"><i class="bi bi-lock-fill"></i> Locked</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<form id="form-bulk-approve" method="POST" action="{{ route('expenses.bulk-approve') }}" style="display:none;">
    @csrf
    <div id="bulk-ids-container"></div>
</form>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#expensesTable').DataTable({
            order: [[2, 'desc']] // Order by Date column by default
        });

        // Toggle all checkboxes (including those not visible in active page)
        $('#select-all-expenses').on('change', function() {
            const rows = table.rows({ 'search': 'applied' }).nodes();
            $('input.expense-checkbox', rows).prop('checked', this.checked).trigger('change');
        });

        // Update selected count and toggle approve button
        $('#expensesTable tbody').on('change', 'input.expense-checkbox', function() {
            const checkedCount = table.$('input.expense-checkbox:checked').length;
            $('#selected-count').text(checkedCount);
            if (checkedCount > 0) {
                $('#btn-bulk-approve').removeClass('d-none');
            } else {
                $('#btn-bulk-approve').addClass('d-none');
            }
        });

        // Handle bulk approve submission
        $('#btn-bulk-approve').on('click', function() {
            if (confirm('Are you sure you want to approve all selected expenses?')) {
                const container = $('#bulk-ids-container');
                container.empty();
                table.$('input.expense-checkbox:checked').each(function() {
                    container.append(`<input type="hidden" name="ids[]" value="${$(this).val()}">`);
                });
                $('#form-bulk-approve').submit();
            }
        });
    });
</script>
@endpush
@endsection