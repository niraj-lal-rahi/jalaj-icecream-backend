@extends('admin.layouts.app')

@section('content')
    <div class="container">
        <h2>Create Item</h2>

        <form action="{{ route('admin.items.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Price</label>
                <input type="number" name="price" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Order</label>
                <input type="number" name="order_by" class="form-control" value="10">
            </div>

            <button class="btn btn-success">Save</button>
            <a href="{{ route('admin.items.index') }}" class="btn btn-secondary">
                Cancel
            </a>
        </form>
    </div>
@endsection
