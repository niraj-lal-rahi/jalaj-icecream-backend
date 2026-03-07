@extends('admin.layouts.app')

@section('content')
    <h3>Sales List</h3>

    <a href="{{ route('admin.sales.create') }}" class="btn btn-success mb-3">
        Add Sales
    </a>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong>Filters</strong>
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
    @if (request('seller_id') || request('date'))
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
                    <strong>Seller Share (60%): ₹ {{ $grand * 0.6 }}</strong>
                </div>
            </div>
        @endforeach
    @endforeach
@endsection
