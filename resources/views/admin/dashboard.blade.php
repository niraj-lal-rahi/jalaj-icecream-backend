@extends('admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Dashboard</h3>
        
        <!-- Date Filter Form -->
        <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2">
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? '' }}" aria-label="Start Date">
            <span>to</span>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? '' }}" aria-label="End Date">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            @if(isset($isFiltered) && $isFiltered)
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary">Clear</a>
            @endif
        </form>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mt-3">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Revenue Metrics Row -->
    <div class="row mt-4">
        <!-- Today's Total -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Today's Total</h6>
                    <h3 class="fw-bold text-primary">₹ {{ number_format($todayTotal) }}</h3>
                    <small class="text-success">Owner (60%): ₹ {{ number_format($todayOwnerShare) }}</small>
                    <br>
                    <small class="text-info">Seller (40%): ₹ {{ number_format($todaySellerShare) }}</small>
                </div>
            </div>
        </div>

        <!-- Yesterday's Total -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Yesterday's Total</h6>
                    <h3 class="fw-bold text-secondary">₹ {{ number_format($yesterdayTotal) }}</h3>
                    <small class="text-success">Owner (60%): ₹ {{ number_format($yesterdayOwnerShare) }}</small>
                    <br>
                    <small class="text-info">Seller (40%): ₹ {{ number_format($yesterdaySellerShare) }}</small>
                </div>
            </div>
        </div>

        <!-- Monthly/Filtered Total -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 {{ (isset($isFiltered) && $isFiltered) ? 'bg-primary' : 'bg-success' }} text-white">
                <div class="card-body">
                    <h6>{{ (isset($isFiltered) && $isFiltered) ? 'Filtered Total' : 'Monthly Total' }}</h6>
                    <h3>₹ {{ number_format($monthlyTotal) }}</h3>
                    <small>Owner (60%): ₹ {{ number_format($monthlyOwnerShare) }}</small>
                    <br>
                    <small>Seller (40%): ₹ {{ number_format($monthlySellerShare) }}</small>
                </div>
            </div>
        </div>

        <!-- Grand Total -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-dark text-white">
                <div class="card-body">
                    <h6>Grand Total (All-time)</h6>
                    <h3>₹ {{ number_format($grandTotal) }}</h3>
                    <small>Owner (60%): ₹ {{ number_format($ownerEarning) }}</small>
                    <br>
                    <small>Seller (40%): ₹ {{ number_format($sellerEarning) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Counts Row -->
    <div class="row mt-4">
        <!-- Total Sellers -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Sellers</h6>
                    <h2 class="fw-bold text-primary">{{ $sellerCount }}</h2>
                    <small class="text-muted">Registered sellers</small>
                </div>
            </div>
        </div>

        <!-- Total Items -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Items</h6>
                    <h2 class="fw-bold text-success">{{ $itemCount }}</h2>
                    <small class="text-muted">Product variants</small>
                </div>
            </div>
        </div>

        <!-- Transactions Count -->
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Transactions</h6>
                    <h2 class="fw-bold text-info">{{ $transactionCount }}</h2>
                    <small class="text-muted">Seller-date pairs</small>
                </div>
            </div>
        </div>

        <!-- Days with Sales -->
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('admin.entry-days.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100" style="cursor: pointer; transition: transform 0.2s;"
                    onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div class="card-body">
                        <h6 class="text-muted">Days with Sales</h6>
                        <h2 class="fw-bold text-warning">{{ $daysWithSales }}</h2>
                        <small class="text-muted">Unique business days</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Top Performers Row -->
    <h4 class="mt-5 mb-3">🏆 Top Performers</h4>
    <div class="row">
        @forelse ($topPerformers as $rank => $seller)
            @php
                $medals = ['🥇', '🥈', '🥉'];
                $medal = $medals[$rank] ?? '';
                $bgColor = $rank === 0 ? 'bg-warning' : ($rank === 1 ? 'bg-secondary' : 'bg-danger');
            @endphp
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm {{ $bgColor }} text-white border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold mb-1">{{ $medal }} {{ $seller['name'] }}</h5>
                                <small>{{ $seller['number'] }}</small>
                            </div>
                            <span class="badge bg-dark">Rank #{{ $rank + 1 }}</span>
                        </div>
                        <div class="mt-3">
                            <h3 class="fw-bold mb-2">{{ number_format($seller['performanceScore'], 1) }}%</h3>
                            <small class="d-block">Performance Score</small>
                            <small class="d-block">Days Active: {{ $seller['daysWithSales'] }}</small>
                            <small class="d-block">Sales: ₹ {{ number_format($seller['totalSalesAmount']) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No performance data available yet.</div>
            </div>
        @endforelse
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('admin.seller-performance.index') }}" class="btn btn-primary">View All Rankings</a>
        </div>
    </div>

    <!-- Red Flags & Actions Row -->
    <div class="row mt-4">
        <!-- Red Flag Entries -->
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('admin.red-flags.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 bg-danger text-white h-100"
                    style="cursor: pointer; transition: transform 0.2s;"
                    onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div class="card-body">
                        <h6>🚩 Red Flag Entries</h6>
                        <h2 class="fw-bold">{{ $redFlagCount }}</h2>
                        <small class="text-light">Flagged transactions</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- Quick Links -->
        <div class="col-md-6 col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Quick Actions</h6>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-primary me-2">View Sales</a>
                    <a href="{{ route('admin.sellers.index') }}" class="btn btn-sm btn-success me-2">Manage Sellers</a>
                    <a href="{{ route('admin.items.index') }}" class="btn btn-sm btn-info me-2">Manage Items</a>
                    <a href="{{ route('admin.sales.index', ['red_flag' => 1]) }}" class="btn btn-sm btn-danger">View Red
                        Flags</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts Section -->
    <h4 class="mt-5 mb-4">📊 Sales Analytics</h4>
    
    <!-- Monthly Sales Trend -->
    <div class="row mb-4">
        <div class="col-12">
            @include('admin.components.sales-trend-chart')
        </div>
    </div>

    <!-- Top Sellers & Best Sellers Row -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            @include('admin.components.top-sellers-chart')
        </div>
        <div class="col-lg-6 mb-4">
            @include('admin.components.avg-sellers-chart')
        </div>
    </div>

    <!-- Item Popularity & Day of Week -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4 mb-lg-0">
            @include('admin.components.items-chart')
        </div>
        <div class="col-lg-6">
            @include('admin.components.day-of-week-chart')
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/admin/charts.js')
    @endpush
@endsection
