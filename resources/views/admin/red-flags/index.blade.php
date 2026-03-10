@extends('admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>🚩 Red Flags</h3>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary">Back to Dashboard</a>
    </div>

    @if ($groupedByDate->isEmpty())
        <div class="alert alert-info">
            <strong>No red flags found.</strong> All transactions are clean!
        </div>
    @else
        @foreach ($groupedByDate as $date => $sales)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        📅 {{ \Carbon\Carbon::parse($date)->format('D, M d, Y') }}
                        <span class="badge bg-light text-danger">{{ $sales->count() }} flag(s)</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Seller</th>
                                    <th>Item</th>
                                    <th>Pick</th>
                                    <th>Returned</th>
                                    <th>Price</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sales as $sale)
                                    <tr class="border-danger border-start border-5">
                                        <td>
                                            <strong>{{ $sale->seller->name }}</strong><br>
                                            <small class="text-muted">{{ $sale->seller->number }}</small>
                                        </td>
                                        <td>{{ $sale->item->name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $sale->pick }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">{{ $sale->returned }}</span>
                                        </td>
                                        <td>₹ {{ number_format($sale->custom_price ?: $sale->item->price) }}</td>
                                        <td>
                                            @if ($sale->remarks)
                                                <small>{{ $sale->remarks }}</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection
