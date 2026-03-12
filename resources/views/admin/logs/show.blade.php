@extends('admin.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="mb-3">
            <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary btn-sm">
                ← Back to Logs
            </a>
        </div>

        @if (isset($error) && $error)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> {{ $error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @else
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">📄 {{ $logData['filename'] }}</h5>
                        <small>
                            {{ $logData['size_formatted'] }} • Modified: {{ $logData['modified_date'] }}
                            @if ($logData['warning'])
                                <span class="badge bg-warning text-dark ms-2">{{ $logData['warning'] }}</span>
                            @endif
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('admin.logs.show', $logData['filename']) }}" class="btn btn-sm btn-light">↻
                            Refresh</a>
                        <a href="{{ route('admin.logs.download', $logData['filename']) }}" class="btn btn-sm btn-success">⬇️
                            Download</a>
                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                            data-bs-target="#clearLogModal">
                            🗑️ Clear
                        </button>
                    </div>
                </div>

                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <pre
                        style="margin: 0; padding: 15px; background-color: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 13px;"><code>
@foreach ($logData['lines'] as $index => $line)
{{ $line }}
@endforeach
</code></pre>
                </div>

                @if ($logData['total_pages'] > 1)
                    <div class="card-footer bg-light">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                @if ($logData['page'] > 1)
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="{{ route('admin.logs.show', ['filename' => $logData['filename'], 'page' => 1]) }}">
                                            First
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="{{ route('admin.logs.show', ['filename' => $logData['filename'], 'page' => $logData['page'] - 1]) }}">
                                            ← Previous
                                        </a>
                                    </li>
                                @endif

                                @for ($i = max(1, $logData['page'] - 2); $i <= min($logData['total_pages'], $logData['page'] + 2); $i++)
                                    <li class="page-item {{ $i === $logData['page'] ? 'active' : '' }}">
                                        <a class="page-link"
                                            href="{{ route('admin.logs.show', ['filename' => $logData['filename'], 'page' => $i]) }}">
                                            {{ $i }}
                                        </a>
                                    </li>
                                @endfor

                                @if ($logData['page'] < $logData['total_pages'])
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="{{ route('admin.logs.show', ['filename' => $logData['filename'], 'page' => $logData['page'] + 1]) }}">
                                            Next →
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="{{ route('admin.logs.show', ['filename' => $logData['filename'], 'page' => $logData['total_pages']]) }}">
                                            Last
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                        <small class="text-muted">
                            Page {{ $logData['page'] }} of {{ $logData['total_pages'] }}
                            ({{ $logData['total_lines'] }} total lines, showing 50 per page)
                        </small>
                    </div>
                @else
                    <div class="card-footer bg-light text-muted">
                        <small>{{ $logData['total_lines'] }} total lines</small>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Clear Log Confirmation Modal -->
    <div class="modal fade" id="clearLogModal" tabindex="-1" aria-labelledby="clearLogModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="clearLogModalLabel">🗑️ Clear Log File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to clear this log file?</strong></p>
                    <p><code>{{ $logData['filename'] }}</code></p>
                    <div class="alert alert-warning mb-0">
                        <small>⚠️ This action cannot be undone. All log entries will be permanently deleted.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('admin.logs.clear', $logData['filename']) }}"
                        style="display: inline;">
                        @method('DELETE')
                        @csrf
                        <button type="submit" class="btn btn-danger">🗑️ Yes, Clear It</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        pre {
            border-radius: 5px;
        }

        code {
            font-weight: normal;
            letter-spacing: 0.5px;
            line-height: 1.6;
        }

        .pagination {
            margin-top: 10px;
        }
    </style>
@endsection
