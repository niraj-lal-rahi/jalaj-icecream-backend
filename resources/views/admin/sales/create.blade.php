@extends('admin.layouts.app')

@section('content')
    <h3>Add Sales</h3>

    <form method="POST" action="{{ route('admin.sales.store') }}">
        @csrf

        <div class="row mb-3">
            <div class="col-md-4">
                <select name="seller_id" class="form-control" required>
                    <option value="">Select Seller</option>
                    @foreach ($sellers as $seller)
                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <input type="date" name="date" value="{{ date('Y-m-d') }}" class="form-control">
            </div>
        </div>

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
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>
                            <input type="number" name="taken[{{ $item->id }}]" class="form-control taken"
                                data-price="{{ $item->price }}">
                        </td>
                        <td>
                            <input type="number" name="returned[{{ $item->id }}]" class="form-control returned">
                        </td>
                        <td>
                            <input type="number" name="price[{{ $item->id }}]" class="form-control price">
                        </td>
                        <td>
                            <input type="text" name="remarks[{{ $item->id }}]" class="form-control">
                        </td>
                        <td class="row-total"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mb-3">
            <input type="checkbox" name="red_flag"> Red Flag
        </div>

        <h4>Total: ₹ <span id="grand-total">0</span></h4>
        <h5>Seller Share (60%): ₹ <span id="share">0</span></h5>

        <button class="btn btn-primary">Save</button>
    </form>

    <script>
        document.addEventListener('input', calculate);

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
