@extends('admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📊 Seller Performance Rankings</h3>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary">Back to Dashboard</a>
    </div>

    @if (empty($performanceData))
        <div class="alert alert-info">
            <strong>No seller performance data available.</strong>
        </div>
    @else
        <div class="row">
            @foreach ($performanceData as $rank => $seller)
                @php
                    $medals = ['🥇', '🥈', '🥉'];
                    $medal = isset($medals[$rank]) ? $medals[$rank] : '';
                    $bgColor =
                        $rank === 0
                            ? 'bg-warning'
                            : ($rank === 1
                                ? 'bg-secondary'
                                : ($rank === 2
                                    ? 'bg-danger'
                                    : 'bg-light'));
                    $textColor = $rank < 3 ? 'text-white' : 'text-dark';
                @endphp

                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card shadow-sm h-100 {{ $bgColor }} {{ $textColor }} border-0">
                        <div class="card-body">
                            <!-- Header with Medal and Rank -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">
                                        {{ $medal }} {{ $seller['name'] }}
                                    </h5>
                                    <small>{{ $seller['number'] }}</small>
                                </div>
                                <span class="badge bg-dark">Rank #{{ $rank + 1 }}</span>
                            </div>

                            <!-- Performance Score (Large) -->
                            <div class="mb-3">
                                <div class="display-5 fw-bold">{{ number_format($seller['performanceScore'], 1) }}%</div>
                                <small class="text-muted">Performance Score</small>
                            </div>

                            <!-- Score Breakdown -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="d-block">Volume Score</small>
                                    <h6 class="mb-0">{{ number_format($seller['volumeScore'], 1) }}%</h6>
                                </div>
                                <div class="col-6">
                                    <small class="d-block">Consistency Score</small>
                                    <h6 class="mb-0">{{ number_format($seller['consistencyScore'], 1) }}%</h6>
                                </div>
                            </div>

                            <!-- Attendance Info -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="d-block">Days Active</small>
                                    <h6 class="mb-0">{{ $seller['daysWithSales'] }}</h6>
                                </div>
                                <div class="col-6">
                                    <small class="d-block">Absent Days</small>
                                    <h6 class="mb-0">{{ $seller['absentDays'] }}</h6>
                                </div>
                            </div>

                            <hr class="my-2">

                            <!-- Sales Info -->
                            <small class="d-block mb-2">Total Sales: <strong>₹
                                    {{ number_format($seller['totalSalesAmount']) }}</strong></small>
                            <small class="d-block">Owner (60%): ₹ {{ number_format($seller['ownerShare']) }}</small>
                            <small>Seller (40%): ₹ {{ number_format($seller['sellerShare']) }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Full Table View -->
        <h4 class="mt-5 mb-3">Detailed Rankings</h4>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Rank</th>
                        <th>Seller Name</th>
                        <th>Phone</th>
                        <th>Performance Score</th>
                        <th>Volume</th>
                        <th>Consistency</th>
                        <th>Days Active</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($performanceData as $rank => $seller)
                        @php
                            $medals = ['🥇', '🥈', '🥉'];
                            $medal = isset($medals[$rank]) ? $medals[$rank] : '•';
                        @endphp
                        <tr>
                            <td><strong>{{ $medal }} #{{ $rank + 1 }}</strong></td>
                            <td>{{ $seller['name'] }}</td>
                            <td>{{ $seller['number'] }}</td>
                            <td>
                                <span class="badge bg-primary">{{ number_format($seller['performanceScore'], 1) }}%</span>
                            </td>
                            <td>{{ number_format($seller['volumeScore'], 1) }}%</td>
                            <td>{{ number_format($seller['consistencyScore'], 1) }}%</td>
                            <td>
                                <span class="badge bg-success">{{ $seller['daysWithSales'] }}</span>
                            </td>
                            <td>₹ {{ number_format($seller['totalSalesAmount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
