@extends('admin.layouts.app')

@section('content')
    <div class="container">
        <h2>Edit Item</h2>

        <form action="{{ route('admin.items.update', $item) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" value="{{ $item->name }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Price</label>
                <input type="number" name="price" value="{{ $item->price }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Order</label>
                <input type="number" name="order_by" value="{{ $item->order_by }}" class="form-control">
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.items.index') }}" class="btn btn-secondary">
                Cancel
            </a>
        </form>
    </div>
@endsection
