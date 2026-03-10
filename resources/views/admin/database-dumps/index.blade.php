@extends('admin.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>💾 Database Dumps Manager</h3>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-secondary me-2">← Back to Dashboard</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDumpModal">
                ➕ Create New Dump
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach ($errors->all() as $error)
                <strong>Error:</strong> {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✓ {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ✗ {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (empty($dumps))
        <div class="alert alert-info text-center py-5">
            <h5>📭 No database dumps found</h5>
            <p class="mb-0">Create your first database dump by clicking the button above.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Filename</th>
                        <th>File Size</th>
                        <th>Created Date</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dumps as $dump)
                        <tr>
                            <td>
                                <code>{{ $dump['name'] }}</code>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $dump['size_formatted'] }}</span>
                            </td>
                            <td>
                                <small>{{ $dump['created_at_formatted'] }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.database-dumps.download', $dump['name']) }}"
                                    class="btn btn-sm btn-success" title="Download">
                                    📥 Download
                                </a>
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="confirmDelete('{{ $dump['name'] }}')" title="Delete">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Modal for Creating Dump -->
    <div class="modal fade" id="createDumpModal" tabindex="-1" aria-labelledby="createDumpLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDumpLabel">💾 Create Database Dump</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This will create a complete backup of your database.</p>
                    <p class="text-muted small">File will be saved as: <code>jalaj_icecream_YYYY-MM-DD_HH-MM-SS.sql</code>
                    </p>
                    <p class="alert alert-warning small mb-0">
                        <strong>⚠️ Note:</strong> Database dump creation may take a few moments depending on database size.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('admin.database-dumps.create') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            ✓ Create Dump
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmLabel">🗑️ Delete Dump</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this dump?</p>
                    <p id="deleteFileName" class="alert alert-warning mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(filename) {
            document.getElementById('deleteFileName').textContent = 'File: ' + filename;
            const deleteForm = document.getElementById('deleteForm');
            deleteForm.action = `/admin/database-dumps/delete/${filename}`;
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }
    </script>
@endsection
