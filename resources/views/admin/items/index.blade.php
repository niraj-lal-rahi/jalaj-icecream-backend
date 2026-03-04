@extends('admin.layouts.app')

@section('content')
    <div class="container">
        <h2>Item List</h2>

        <a href="{{ route('admin.items.create') }}" class="btn btn-primary mb-3">
            Add Item
        </a>


        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Order</th>
                    <th width="180">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->price }}</td>
                        <td>{{ $item->order_by }}</td>
                        <td>
                            <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-warning btn-sm">Edit</a>

                            <form action="{{ route('admin.items.destroy', $item) }}" method="POST"
                                style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this item?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
