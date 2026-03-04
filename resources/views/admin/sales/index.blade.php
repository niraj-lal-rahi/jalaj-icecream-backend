@extends('admin.layouts.app')

@section('content')
    <h3>Sales List</h3>

    <a href="{{ route('admin.sales.create') }}" class="btn btn-success mb-3">
        Add Sales
    </a>

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
