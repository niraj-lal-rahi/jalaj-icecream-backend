@extends('admin.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="mb-3">
            <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary btn-sm">
                ← Back to Files
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
                        <h5 class="mb-0">📄 {{ $fileData['filename'] }}</h5>
                        <small>
                            Path: <code style="color: white;">{{ $fileData['path'] }}</code><br>
                            {{ $fileData['size_formatted'] }} • Modified: {{ $fileData['modified_date'] }}
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('admin.logs.view-file', ['path' => $fileData['path']]) }}"
                            class="btn btn-sm btn-light">↻ Refresh</a>
                        <a href="{{ route('admin.logs.download-file', ['path' => $fileData['path']]) }}"
                            class="btn btn-sm btn-success">⬇️ Download</a>
                    </div>
                </div>

                @if ($fileData['is_binary'] ?? false)
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Binary File:</strong> This file cannot be displayed as text.
                            <a href="{{ route('admin.logs.download-file', ['path' => $fileData['path']]) }}"
                                class="btn btn-sm btn-warning">
                                Download File
                            </a>
                        </div>
                    </div>
                @else
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <pre
                            style="margin: 0; padding: 15px; background-color: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 13px;"><code>
@foreach ($fileData['lines'] as $line)
{{ $line }}
@endforeach
</code></pre>
                    </div>

                    @if ($fileData['total_pages'] > 1)
                        <div class="card-footer bg-light">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    @if ($fileData['page'] > 1)
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ route('admin.logs.view-file', ['path' => $fileData['path'], 'page' => 1]) }}">
                                                First
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ route('admin.logs.view-file', ['path' => $fileData['path'], 'page' => $fileData['page'] - 1]) }}">
                                                ← Previous
                                            </a>
                                        </li>
                                    @endif

                                    @for ($i = max(1, $fileData['page'] - 2); $i <= min($fileData['total_pages'], $fileData['page'] + 2); $i++)
                                        <li class="page-item {{ $i === $fileData['page'] ? 'active' : '' }}">
                                            <a class="page-link"
                                                href="{{ route('admin.logs.view-file', ['path' => $fileData['path'], 'page' => $i]) }}">
                                                {{ $i }}
                                            </a>
                                        </li>
                                    @endfor

                                    @if ($fileData['page'] < $fileData['total_pages'])
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ route('admin.logs.view-file', ['path' => $fileData['path'], 'page' => $fileData['page'] + 1]) }}">
                                                Next →
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ route('admin.logs.view-file', ['path' => $fileData['path'], 'page' => $fileData['total_pages']]) }}">
                                                Last
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                            <small class="text-muted">
                                Page {{ $fileData['page'] }} of {{ $fileData['total_pages'] }}
                                ({{ $fileData['total_lines'] }} total lines, showing 50 per page)
                            </small>
                        </div>
                    @else
                        <div class="card-footer bg-light text-muted">
                            <small>{{ $fileData['total_lines'] }} total lines</small>
                        </div>
                    @endif
                @endif
            </div>
        @endif
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
