@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('breadcrumb')
<li class="breadcrumb-item active">Notifications</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-3" style="background: var(--card-bg); border-bottom: 1px solid var(--card-border);">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary me-3">
                        <i class="bi bi-bell-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-700" style="color: var(--text-primary);">Notification Center</h5>
                        <small class="text-muted">Manage and view your system updates</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <form method="GET" action="{{ route('notifications.index') }}" class="d-flex align-items-center">
                        <div class="input-group input-group-sm" style="min-width: 260px;">
                            <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--card-border); color: var(--text-secondary);"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="notificationSearchInput" class="form-control border-start-0" placeholder="Search notifications..." value="{{ $search ?? '' }}" style="border-color: var(--card-border);">
                            @if(!empty($search))
                            <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear search"><i class="bi bi-x-lg"></i></a>
                            @endif
                            <button type="submit" class="btn btn-sm btn-accent">Search</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('notifications.clear') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-custom">
                            <i class="bi bi-check-all me-1"></i> Mark All as Read
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0" style="background: var(--card-bg);">
                @if(!empty($search))
                <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="background: rgba(0, 136, 204, 0.05); border-color: var(--card-border) !important;">
                    <span class="small" style="color: var(--text-secondary);">
                        <i class="bi bi-funnel-fill me-1 text-primary"></i> Filtering by: <strong>"{{ $search }}"</strong> ({{ $notifications->total() }} result{{ $notifications->total() != 1 ? 's' : '' }})
                    </span>
                    <a href="{{ route('notifications.index') }}" class="btn btn-xs btn-outline-secondary py-0 px-2 style='font-size:0.75rem;'"><i class="bi bi-x-circle me-1"></i> Clear Filter</a>
                </div>
                @endif
                @if($notifications->isEmpty())
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex p-4 mb-3 text-secondary" style="width: 80px; height: 80px; align-items: center; justify-content: center; font-size: 2rem;">
                        <i class="bi {{ !empty($search) ? 'bi-search' : 'bi-bell-slash' }}"></i>
                    </div>
                    <h6 class="fw-600 text-muted">{{ !empty($search) ? 'No notifications matching "' . $search . '"' : 'All caught up!' }}</h6>
                    <p class="text-muted small mb-0">{{ !empty($search) ? 'Try searching with a different keyword.' : 'You have no system notifications at the moment.' }}</p>
                    @if(!empty($search))
                    <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-custom mt-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Clear Search Filter</a>
                    @endif
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($notifications as $notification)
                    <div class="list-group-item list-group-item-action p-3 {{ !$notification->is_read ? 'border-start border-primary border-4' : '' }}" style="background: {{ !$notification->is_read ? 'rgba(2, 132, 199, 0.05)' : 'var(--card-bg)' }}; border-color: var(--card-border);" id="notification-row-{{ $notification->id }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex gap-3">
                                <div class="rounded-circle p-2 {{ !$notification->is_read ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; flex-shrink: 0;">
                                    <i class="bi {{ !$notification->is_read ? 'bi-envelope-fill' : 'bi-envelope-open' }}"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-700" style="color: var(--text-primary);">{{ $notification->title }}</h6>
                                    <p class="mb-1 text-secondary small">{{ $notification->message }}</p>
                                    @if($notification->destination_url)
                                    <div class="my-2">
                                        <a href="{{ route('notifications.go', ['notification' => $notification, 'redirect' => $notification->destination_url]) }}" class="btn btn-xs btn-primary text-white text-decoration-none py-1 px-2.5 rounded-2 shadow-xs fw-600" style="font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                                            <i class="bi bi-arrow-right-circle-fill"></i> View Details
                                        </a>
                                    </div>
                                    @endif
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            @if(!$notification->is_read)
                            <button class="btn btn-xs btn-outline-primary mark-as-read-btn" data-id="{{ $notification->id }}" title="Mark as read">
                                <i class="bi bi-check-lg"></i> Mark read
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @if($notifications->hasPages())
            <div class="card-footer border-0 py-3 d-flex justify-content-between align-items-center" style="background: var(--card-bg); border-top: 1px solid var(--card-border) !important;">
                <div class="w-100">
                    {{ $notifications->links('pagination::bootstrap-5') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).on('click', '.mark-as-read-btn', function() {
        const btn = $(this);
        const notificationId = btn.data('id');

        $.ajax({
            url: `/notifications/${notificationId}/read`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $(`#notification-row-${notificationId}`)
                        .removeClass('bg-light border-start border-primary border-4')
                        .find('.rounded-circle')
                        .removeClass('bg-primary-subtle text-primary')
                        .addClass('bg-secondary-subtle text-secondary')
                        .find('i')
                        .removeClass('bi-envelope-fill')
                        .addClass('bi-envelope-open');

                    btn.fadeOut();

                    // Trigger dynamic poll to update navbar bell immediately
                    if (typeof window.pollNotifications === 'function') {
                        window.pollNotifications();
                    }
                }
            },
            error: function(xhr) {
                console.error('Failed to mark notification as read: ', xhr);
            }
        });
    });
</script>
@endpush
@endsection