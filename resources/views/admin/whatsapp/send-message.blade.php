@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h3>
                    <i class="bi bi-whatsapp"></i> Send WhatsApp Message
                </h3>
                <p class="text-muted">Send direct WhatsApp messages to customers or contacts</p>
            </div>
        </div>



        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-chat-left-text"></i> Compose Message
                        </h5>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.whatsapp.send') }}" id="messageForm"
                            enctype="multipart/form-data">
                            @csrf

                            <!-- Phone Number Input -->
                            <div class="mb-4">
                                <label for="phone_number" class="form-label">
                                    <span class="fw-bold">Phone Number</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                                        id="phone_number" name="phone_number" placeholder="+91XXXXXXXXXX or 91XXXXXXXXXX"
                                        value="{{ old('phone_number') }}" maxlength="15" required>
                                </div>
                                @error('phone_number')
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle"></i>
                                    Enter an Indian phone number in format: <code>+91XXXXXXXXXX</code> or
                                    <code>91XXXXXXXXXX</code>
                                </small>
                            </div>

                            <!-- Message Textarea -->
                            <div class="mb-4">
                                <label for="message" class="form-label">
                                    <span class="fw-bold">Message</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6"
                                    placeholder="Enter your message here..." maxlength="4096" required>{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle"></i>
                                    Maximum 4096 characters allowed
                                </small>
                            </div>

                            <!-- Character Counter -->
                            <div class="mb-4">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" id="characterProgress" role="progressbar"
                                        style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="4096">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Characters: <span id="characterCount">0</span> / 4096
                                </small>
                            </div>

                            <!-- File Attachment (Optional) -->
                            <div class="mb-4">
                                <label for="attachment" class="form-label">
                                    <span class="fw-bold">📎 Attachment</span>
                                    <span class="badge bg-secondary">Optional</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-paperclip"></i>
                                    </span>
                                    <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                                        id="attachment" name="attachment"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.ogg">
                                </div>
                                @error('attachment')
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle"></i>
                                    Supported: PDF, Images (JPG, PNG), Videos (MP4), Audio (MP3), Documents (DOC, XLS, CSV)
                                    | Max: 16MB
                                </small>
                                <small class="text-info d-block mt-2">
                                    <i class="bi bi-lightbulb"></i>
                                    The message will be sent first, followed by the attachment after a brief delay.
                                </small>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Send Message
                                </button>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-lg">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle"></i> Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Phone Number Format</h6>
                        <p class="text-muted small">
                            WhatsApp requires phone numbers to be in international format. For India:
                        </p>
                        <ul class="list-unstyled small text-muted">
                            <li><code>+91XXXXXXXXXX</code> ✓ Preferred</li>
                            <li><code>91XXXXXXXXXX</code> ✓ Accepted</li>
                            <li><code>XXXXXXXXXX</code> ✗ Not accepted</li>
                        </ul>

                        <hr class="my-3">

                        <h6 class="fw-bold mb-3">Message Guidelines</h6>
                        <ul class="list-unstyled small text-muted">
                            <li><i class="bi bi-check-circle text-success"></i> Keep messages clear and concise</li>
                            <li><i class="bi bi-check-circle text-success"></i> Use line breaks for readability</li>
                            <li><i class="bi bi-check-circle text-success"></i> Maximum 4096 characters</li>
                            <li><i class="bi bi-check-circle text-success"></i> Messages are logged for audit</li>
                        </ul>

                        <hr class="my-3">

                        <h6 class="fw-bold mb-3">📎 File Attachment (NEW!)</h6>
                        <ul class="list-unstyled small text-muted">
                            <li><i class="bi bi-check-circle text-success"></i> Attach documents, images, or videos</li>
                            <li><i class="bi bi-check-circle text-success"></i> Maximum file size: 16MB</li>
                            <li><i class="bi bi-check-circle text-success"></i> Supported formats: PDF, DOC, XLS, JPG, PNG,
                                MP4, MP3</li>
                            <li><i class="bi bi-check-circle text-success"></i> File is sent after the message text</li>
                        </ul>

                        <hr class="my-3">

                        <h6 class="fw-bold mb-3">Important Notes</h6>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            Ensure the recipient has WhatsApp installed and the phone number is correct before sending.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .input-group-text {
            border-color: #dee2e6 !important;
        }
    </style>

    <script>
        const messageField = document.getElementById('message');
        const characterCount = document.getElementById('characterCount');
        const characterProgress = document.getElementById('characterProgress');

        if (messageField) {
            messageField.addEventListener('input', function() {
                const count = this.value.length;
                const maxLength = 4096;
                const percentage = (count / maxLength) * 100;

                characterCount.textContent = count;
                characterProgress.style.width = percentage + '%';

                // Change color based on usage
                if (percentage > 90) {
                    characterProgress.className = 'progress-bar bg-danger';
                } else if (percentage > 70) {
                    characterProgress.className = 'progress-bar bg-warning';
                } else {
                    characterProgress.className = 'progress-bar bg-success';
                }
            });
        }

        // Initialize counter on page load
        messageField.dispatchEvent(new Event('input'));

        // Form submission feedback
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
        });
    </script>
@endsection
