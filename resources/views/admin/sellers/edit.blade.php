@extends('admin.layouts.app')

@section('content')
    <h3>Edit Seller</h3>

    <form action="{{ route('admin.sellers.update', $seller->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ $seller->name }}" required>
        </div>

        <div class="mb-3">
            <label>Number</label>
            <input type="text" name="number" class="form-control" value="{{ $seller->number }}" required>
        </div>

        <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control" required>{{ $seller->address }}</textarea>
        </div>

        <button class="btn btn-primary">Update</button>
        <a href="{{ route('admin.sellers.index') }}" class="btn btn-secondary">Back</a>
    </form>
@endsection
