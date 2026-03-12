@extends('admin.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>📋 System Logs & Files</h3>
            <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-outline-primary">
                ↻ Refresh
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Log Files Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📄 Log Files (storage/logs/)</h5>
            </div>
            <div class="card-body">
                @if (empty($logFiles))
                    <div class="alert alert-info mb-0">
                        No log files found.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                    <th style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logFiles as $file)
                                    <tr>
                                        <td>
                                            <code>{{ $file['name'] }}</code>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $file['size_formatted'] }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted" title="{{ $file['modified_date'] }}">
                                                {{ \Carbon\Carbon::createFromTimestamp($file['modified'])->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.logs.show', $file['name']) }}"
                                                class="btn btn-sm btn-info" title="View">
                                                👁️ View
                                            </a>
                                            <a href="{{ route('admin.logs.download', $file['name']) }}"
                                                class="btn btn-sm btn-success" title="Download">
                                                ⬇️ Download
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Clear"
                                                data-bs-toggle="modal" data-bs-target="#clearLogModal{{ $loop->index }}"
                                                onclick="event.preventDefault();">
                                                🗑️ Clear
                                            </button>

                                            <!-- Clear Log Modal -->
                                            <div class="modal fade" id="clearLogModal{{ $loop->index }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title">🗑️ Clear {{ $file['name'] }}</h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-2">Clear this log file?</p>
                                                            <div class="alert alert-warning mb-0">
                                                                <small>⚠️ Cannot be undone</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <form method="POST"
                                                                action="{{ route('admin.logs.clear', $file['name']) }}"
                                                                style="display: inline;">
                                                                @method('DELETE')
                                                                @csrf
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-danger">Clear</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Storage Files Section -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">📁 Storage Files (storage/)</h5>
            </div>
            <div class="card-body">
                @if (empty($storageFiles))
                    <div class="alert alert-info mb-0">
                        No files found in storage directory.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Path</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                    <th style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($storageFiles as $file)
                                    <tr>
                                        <td>
                                            <code>{{ $file['path'] }}</code>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ strtoupper($file['extension'] ?: 'FILE') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $file['size_formatted'] }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted" title="{{ $file['modified_date'] }}">
                                                {{ \Carbon\Carbon::createFromTimestamp($file['modified'])->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td>
                                            @if (in_array(strtolower($file['extension']), ['txt', 'log', 'csv', 'json', 'xml', 'md', 'php', 'html']))
                                                <a href="{{ route('admin.logs.view-file', ['path' => $file['path']]) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    👁️ View
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.logs.download-file', ['path' => $file['path']]) }}"
                                                class="btn btn-sm btn-success" title="Download">
                                                ⬇️ Download
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        code {
            background-color: #f8f9fa;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
@endsection
