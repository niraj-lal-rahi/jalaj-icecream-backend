@extends('admin.layouts.app')

@section('content')
    <h3>Edit Sales</h3>

    <p><strong>Seller:</strong> {{ $seller->name }}</p>
    <p><strong>Date:</strong> {{ $date }}</p>

    <form method="POST" action="{{ route('admin.sales.update.group', [$seller->id, $date]) }}">
        @csrf
        @method('PUT')

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Taken</th>
                    <th>Returned</th>
                    <th>Custom Price</th>
                    <th>Remarks</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $sale = $sales[$item->id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $item->name }}</td>

                        <td>
                            <input type="number" name="taken[{{ $item->id }}]" value="{{ $sale->pick ?? 0 }}"
                                class="form-control taken" data-price="{{ $item->price }}">
                        </td>

                        <td>
                            <input type="number" name="returned[{{ $item->id }}]" value="{{ $sale->returned ?? 0 }}"
                                class="form-control returned">
                        </td>

                        <td>
                            <input type="number" name="price[{{ $item->id }}]" value="{{ $sale->custom_price ?? 0 }}"
                                class="form-control price">
                        </td>

                        <td>
                            <input type="text" name="remarks[{{ $item->id }}]" value="{{ $sale->remarks ?? '' }}"
                                class="form-control">
                        </td>

                        <td class="row-total"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mb-3">
            <input type="checkbox" name="red_flag"
                {{ isset($sales->first()->red_flag) && $sales->first()->red_flag ? 'checked' : '' }}>
            Red Flag
        </div>

        <h4>Total: ₹ <span id="grand-total">0</span></h4>
        <h5>Seller Share (60%): ₹ <span id="share">0</span></h5>

        <button class="btn btn-primary">Update</button>
    </form>

    <script>
        document.addEventListener('input', calculate);
        window.onload = calculate;

        function calculate() {
            let total = 0;

            document.querySelectorAll('.taken').forEach(input => {

                let row = input.closest('tr');

                let taken = parseInt(input.value) || 0;
                let returned = parseInt(row.querySelector('.returned').value) || 0;
                let custom = parseInt(row.querySelector('.price').value) || 0;
                let price = parseInt(input.dataset.price);

                let rowTotal = (taken - returned) * (custom || price);

                row.querySelector('.row-total').innerText = rowTotal;

                total += rowTotal;
            });

            document.getElementById('grand-total').innerText = total;
            document.getElementById('share').innerText = total * 0.6;
        }
    </script>
@endsection
