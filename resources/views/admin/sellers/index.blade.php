@extends('admin.layouts.app')

@section('content')

    <div class="d-flex justify-content-between mb-3">
        <h3>Sellers</h3>
        @can('seller.create')
            <a href="{{ route('admin.sellers.create') }}" class="btn btn-primary">
                + Add Seller
            </a>
        @endcan
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Number</th>
                <th>Address</th>
                <th>Documents</th>
                <th width="150">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sellers as $seller)
                <tr>
                    <td>{{ $seller->id }}</td>
                    <td>{{ $seller->name }}</td>
                    <td>{{ $seller->number }}</td>
                    <td>{{ $seller->address }}</td>
                    <td>
                        @foreach ($seller->documents as $doc)
                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="badge bg-info">
                                View
                            </a>
                        @endforeach
                    </td>
                    <td>
                        @can('seller.edit')
                            <a href="{{ route('admin.sellers.edit', $seller->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        @endcan

                        @can('seller.delete')
                            <form action="{{ route('admin.sellers.destroy', $seller->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No sellers found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

@endsection
