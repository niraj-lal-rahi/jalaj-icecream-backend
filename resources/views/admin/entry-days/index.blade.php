@extends('admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📅 Entry Days (Seller Attendance)</h3>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary">Back to Dashboard</a>
    </div>

    @if (empty($entryDays))
        <div class="alert alert-info">
            <strong>No entry days found.</strong> No sales recorded yet.
        </div>
    @else
        @foreach ($entryDays as $day)
            <div class="card mb-3 shadow-sm">
                <a href="{{ route('admin.entry-days.show', ['date' => $day['date']]) }}"
                    style="text-decoration: none; color: inherit;">
                    <div class="card-header bg-primary text-white" style="cursor: pointer;">
                        <h5 class="mb-0">
                            📅 {{ \Carbon\Carbon::parse($day['date'])->format('D, M d, Y') }}
                            <span class="badge bg-light text-primary ms-2">
                                ✓ {{ $day['presentCount'] }} Present | ✗ {{ $day['absentCount'] }} Absent
                            </span>
                        </h5>
                    </div>
                </a>
                <div class="card-body">
                    <div class="row">
                        <!-- Present Sellers -->
                        <div class="col-md-6">
                            <h6 class="text-success fw-bold mb-3">✓ Present Sellers ({{ $day['presentCount'] }})</h6>
                            @if ($day['presentSellers']->isEmpty())
                                <p class="text-muted">No present sellers</p>
                            @else
                                <div class="list-group list-group-sm">
                                    @foreach ($day['presentSellers'] as $seller)
                                        <div
                                            class="list-group-item border-left-success border-start border-5 border-success">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">✓ {{ $seller->name }}</h6>
                                                    <small class="text-muted">{{ $seller->number }}</small>
                                                </div>
                                                <span class="badge bg-success">Present</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Absent Sellers -->
                        <div class="col-md-6">
                            <h6 class="text-danger fw-bold mb-3">✗ Absent Sellers ({{ $day['absentCount'] }})</h6>
                            @if ($day['absentSellers']->isEmpty())
                                <p class="text-muted">All sellers present</p>
                            @else
                                <div class="list-group list-group-sm">
                                    @foreach ($day['absentSellers'] as $seller)
                                        <div class="list-group-item border-start border-5 border-danger">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">✗ {{ $seller->name }}</h6>
                                                    <small class="text-muted">{{ $seller->number }}</small>
                                                </div>
                                                <span class="badge bg-danger">Absent</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection
