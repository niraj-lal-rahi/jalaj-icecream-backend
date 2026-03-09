@extends('admin.layouts.app')

@section('content')
    <h3>Sales List</h3>

    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('admin.sales.create') }}" class="btn btn-success">
            Add Sales
        </a>
        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#sendReportModal">
            <i class="bi bi-send"></i> Send Report via WhatsApp
        </button>
    </div>



    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Filters (Optional - Default shows today's sales)</strong>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.sales.index') }}" class="row g-3">
                <!-- Seller Filter -->
                <div class="col-md-4">
                    <label for="seller_id" class="form-label">Seller</label>
                    <select name="seller_id" id="seller_id" class="form-control">
                        <option value="">-- All Sellers --</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                {{ $seller->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Filter -->
                <div class="col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                </div>

                <!-- Action Buttons -->
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.sales.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filters Display -->
    @if (request('seller_id') || request('date') || request('seller_name'))
        <div class="alert alert-info mb-3">
            <strong>Active Filters:</strong>
            @if (request('seller_id'))
                Seller: <strong>{{ $sellers->find(request('seller_id'))->name }}</strong>
            @endif
            @if (request('date'))
                @if (request('seller_id'))
                    |
                @endif
                Date: <strong>{{ request('date') }}</strong>
            @endif
        </div>
    @else
        <div class="alert alert-success mb-3">
            <strong>Showing:</strong> Today's Sales ({{ date('Y-m-d') }})
            <br><small>Use filters above to view other dates or sellers</small>
        </div>
    @endif

    <!-- Overall Summary -->
    @php
        $overallTotal = 0;
        $sellerCount = 0;
        foreach ($sales as $date => $sellerGroup) {
            $sellerCount += count($sellerGroup);
            foreach ($sellerGroup as $sellerId => $rows) {
                foreach ($rows as $sale) {
                    $overallTotal += $sale->total;
                }
            }
        }
    @endphp

    @if ($overallTotal > 0)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Overall Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6>Number of Sellers</h6>
                        <h4 class="text-warning">{{ $sellerCount }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Total Amount</h6>
                        <h4 class="text-primary">₹ {{ number_format($overallTotal, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Seller Share (40%)</h6>
                        <h4 class="text-info">₹ {{ number_format($overallTotal * 0.4, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Owner Share (60%)</h6>
                        <h4 class="text-success">₹ {{ number_format($overallTotal * 0.6, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @foreach ($sales as $date => $sellerGroup)
        @foreach ($sellerGroup as $sellerId => $rows)
            <div class="card mb-3">
                <div class="card-header">
                    Date: {{ $date }} |
                    Seller: {{ $rows->first()->seller->name }}
                </div>

                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Net Qty</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grand = 0; @endphp
                            @foreach ($rows as $sale)
                                @php $grand += $sale->total; @endphp
                                <tr>
                                    <td>{{ $sale->item->name }}</td>
                                    <td>{{ $sale->pick - $sale->returned }}</td>
                                    <td>{{ $sale->total }}</td>
                                    <td>
                                        <a href="{{ route('admin.sales.edit.group', [$rows->first()->seller_id, $date]) }}"
                                            class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.sales.destroy', $sale) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <strong>Total: ₹ {{ $grand }}</strong><br>
                    <strong>Seller Share (40%): ₹ {{ round($grand * 0.4, 2) }}</strong><br>
                    <strong>Owner Share (60%): ₹ {{ round($grand * 0.6, 2) }}</strong>
                </div>
            </div>
        @endforeach
    @endforeach

    <!-- Send Report Modal -->
    <div class="modal fade" id="sendReportModal" tabindex="-1" aria-labelledby="sendReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendReportModalLabel">Send Sales Report via WhatsApp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.sales.send-report') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="report_date" class="form-label">Select Date</label>
                            <input type="date" name="date" id="report_date" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                            <small class="text-muted">Choose the date for the sales report</small>
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i>
                            This will send CSV report to admin WhatsApp number:
                            <strong>{{ config('whatsapp.admin_phone_number') }}</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send"></i> Send Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
