@extends('admin.layouts.app')

@section('content')
    <h3>Add Seller</h3>

    <form action="{{ route('admin.sellers.store') }}" method="POST" enctype="multipart/form-data">

        @csrf

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
            <label>Number</label>
            <input type="text" name="number" class="form-control" value="{{ old('number') }}" required>
        </div>

        <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control" required>{{ old('address') }}</textarea>
        </div>

        <div class="mb-3">
            <label>Documents</label>
            <input type="file" name="documents[]" class="form-control" multiple>
        </div>

        <button class="btn btn-success">Save</button>
        <a href="{{ route('admin.sellers.index') }}" class="btn btn-secondary">Back</a>

    </form>
@endsection
