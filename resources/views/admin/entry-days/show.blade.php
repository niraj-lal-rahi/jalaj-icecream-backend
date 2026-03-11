@extends('admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📅 Sales for {{ \Carbon\Carbon::parse($date)->format('D, M d, Y') }}</h3>
        <a href="{{ route('admin.entry-days.index') }}" class="btn btn-sm btn-secondary">← Back to Entry Days</a>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Sales</h6>
                    <h4 class="mb-0">रु {{ number_format($totalSales, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Items Sold</h6>
                    <h4 class="mb-0">{{ $totalItems }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Items Returned</h6>
                    <h4 class="mb-0">{{ $totalReturned }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales by Seller -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Sales Details</h5>
        </div>
        <div class="card-body p-0">
            @forelse ($sellersWithSales as $seller)
                <div class="p-3 border-bottom">
                    <h6 class="fw-bold mb-3">
                        👤 {{ $seller->name }}
                        <span class="badge bg-info">{{ $seller->number }}</span>
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Pick</th>
                                    <th class="text-center">Returned</th>
                                    <th class="text-center">Net</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sales->where('seller_id', $seller->id) as $sale)
                                    @php
                                        $price = $sale->custom_price ?: $sale->item->price;
                                        $net = $sale->pick - $sale->returned;
                                        $total = $net * $price;
                                    @endphp
                                    <tr class="@if ($sale->red_flag) table-danger @endif">
                                        <td>
                                            <strong>{{ $sale->item->name }}</strong>
                                            @if ($sale->remarks)
                                                <br><small class="text-muted">{{ $sale->remarks }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $sale->pick }}</td>
                                        <td class="text-center">{{ $sale->returned }}</td>
                                        <td class="text-center fw-bold">{{ $net }}</td>
                                        <td class="text-end">रु {{ number_format($price, 2) }}</td>
                                        <td class="text-end fw-bold">रु {{ number_format($total, 2) }}</td>
                                        <td class="text-center">
                                            @if ($sale->red_flag)
                                                <span class="badge bg-danger">🚩 Flag</span>
                                            @else
                                                <span class="badge bg-success">✓</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">Subtotal for {{ $seller->name }}:</td>
                                    <td class="text-end">
                                        रु
                                        {{ number_format(
                                            $sales->where('seller_id', $seller->id)->sum(function ($sale) {
                                                $price = $sale->custom_price ?: $sale->item->price;
                                                return ($sale->pick - $sale->returned) * $price;
                                            }),
                                            2,
                                        ) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @empty
                <div class="p-3">
                    <p class="text-muted">No sales found for this date.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
