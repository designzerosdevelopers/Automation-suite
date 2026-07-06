@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-phone'></i> Call Logs
    </h1>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>All Calls (From Vapi)</h2>
                <div class="panel-toolbar">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">
                        <i class="fal fa-phone-alt mr-1"></i> Make a Call
                    </a>
                    <button onclick="window.location.reload();" class="btn btn-info btn-sm ml-1">
                        <i class="fal fa-sync mr-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error') || isset($error))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') ?? $error }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>Call ID</th>
                                    {{-- <th>Customer</th> --}}
                                    <th>Phone</th>
                                    <th>Direction</th>
                                    <th>Status</th>
                                    {{-- <th>Duration</th> --}}
                                    <th>Messages</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($calls as $call)
                                <tr>
                                    <td>
                                        <code class="text-sm">{{ $call['id'] ?? '-' }}</code>
                                    </td>
                                    {{-- <td>
                                        <strong>{{ $call['customer']['name'] ?? $call['customerName'] ?? 'Unknown' }}</strong>
                                    </td> --}}
                                    <td>{{ $call['customer']['number'] ?? $call['from'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ ($call['direction'] ?? 'inbound') == 'inbound' ? 'badge-success' : 'badge-info' }}">
                                            {{ ucfirst($call['direction'] ?? 'inbound') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ ($call['status'] ?? '') == 'ended' ? 'badge-success' : (($call['status'] ?? '') == 'in-progress' ? 'badge-info' : 'badge-warning') }}">
                                            {{ ucfirst($call['status'] ?? 'unknown') }}
                                        </span>
                                    </td>
                                    {{-- <td>
                                        @if(isset($call['duration']) && $call['duration'] > 0)
                                            {{ gmdate('i:s', $call['duration']) }}
                                        @else
                                            -
                                        @endif
                                    </td> --}}
                                    <td>
                                        @php
                                            $hasTranscript = false;
                                            $messageCount = 0;
                                            if (isset($call['transcript']) && is_string($call['transcript']) && !empty($call['transcript'])) {
                                                $hasTranscript = true;
                                                $messageCount = substr_count($call['transcript'], "\n");
                                            } elseif (isset($call['messages']) && is_array($call['messages'])) {
                                                $hasTranscript = true;
                                                $messageCount = count($call['messages']);
                                            }
                                        @endphp
                                        @if($hasTranscript)
                                            <span class="badge badge-success">
                                                <i class="fal fa-check-circle"></i> {{ $messageCount }} messages
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fal fa-minus-circle"></i> No
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($call['createdAt']))
                                            {{ \Carbon\Carbon::parse($call['createdAt'])->format('Y-m-d H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="openTranscriptModal('{{ $call['id'] }}')">
                                                <i class="fal fa-comment-alt"></i> Transcript
                                            </button>
                                            {{-- <a href="{{ route('admin.call-logs.show', $call['id']) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fal fa-eye"></i>
                                            </a> --}}
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fal fa-phone fa-2x d-block text-muted mb-2"></i>
                                        <p class="text-muted">No calls found in Vapi.</p>
                                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">
                                            <i class="fal fa-phone-alt mr-1"></i> Make First Call
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Transcript Modal --}}
<div class="modal fade" id="transcriptModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fal fa-phone-alt mr-2"></i>
                    Call Transcript
                    <br>
                    <small class="text-muted" id="callInfo">Loading...</small>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="transcriptContent">
                <div class="text-center py-4">
                    <div class="loading-spinner"></div>
                    <p class="mt-2">Loading transcript...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                {{-- <button type="button" class="btn btn-primary" onclick="debugTranscript()">
                    <i class="fal fa-bug"></i> Debug
                </button> --}}
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .bg-primary-soft {
        background-color: rgba(0, 123, 255, 0.08);
    }
    .border-left-primary {
        border-left: 4px solid #007bff;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8;
    }
    .call-transcript {
        max-height: 500px;
        overflow-y: auto;
        padding-right: 5px;
    }
    .call-transcript .message {
        transition: all 0.2s;
        border-radius: 8px;
    }
    .call-transcript .message:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }
    .badge-lg {
        font-size: 14px;
        padding: 8px 16px;
    }
    .text-sm {
        font-size: 12px;
    }
    .modal-backdrop {
        z-index: 1040 !important;
    }
    .modal {
        z-index: 1050 !important;
    }
    .loading-spinner {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .debug-json {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 4px;
        font-size: 12px;
        max-height: 300px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    .message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    .message-assistant {
        background: #e3f2fd;
        border-left: 4px solid #1976d2;
    }
    .message-user {
        background: #f5f5f5;
        border-left: 4px solid #9e9e9e;
    }
</style>
@endsection

@section('scripts')
<script>
    let currentTranscriptData = null;

    // Get the base URL for transcript API
    const transcriptUrl = '{{ url("admin/call-logs/transcript") }}';
    
    // Open transcript modal and fetch data
    function openTranscriptModal(callId) {
        // Show modal with loading state
        $('#transcriptModal').modal('show');
        $('#callInfo').text('Call ID: ' + callId);
        $('#transcriptContent').html(`
            <div class="text-center py-4">
                <div class="loading-spinner"></div>
                <p class="mt-2">Loading transcript...</p>
            </div>
        `);
        
        // Fetch transcript from API
        const url = transcriptUrl + '/' + callId;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                currentTranscriptData = data;
                renderTranscript(data);
            })
            .catch(error => {
                $('#transcriptContent').html(`
                    <div class="alert alert-danger">
                        <i class="fal fa-exclamation-triangle"></i>
                        Failed to load transcript: ${error.message}
                        <br>
                        <small>URL: ${url}</small>
                        <br><br>
                        <button class="btn btn-sm btn-outline-secondary" onclick="debugTranscript()">
                            <i class="fal fa-bug"></i> Debug
                        </button>
                    </div>
                `);
            });
    }

    // Render transcript data
    function renderTranscript(data) {
        let html = '';
        
        // Call info
        if (data.call) {
            const call = data.call;
            const customerName = call.customer?.name || call.customerName || 'Unknown';
            const customerNumber = call.customer?.number || call.from || 'No phone';
            const duration = call.duration ? ' (Duration: ' + formatDuration(call.duration) + ')' : '';
            const status = call.status ? ' - Status: ' + call.status : '';
            const endedReason = call.endedReason ? ' - Ended: ' + call.endedReason.replace(/-/g, ' ') : '';
            
            $('#callInfo').html(
                '<strong>' + customerName + '</strong> - ' + customerNumber + 
                duration + status + endedReason
            );
        }
        
        // Get transcript messages
        let messages = [];
        
        // Try to get from transcript array
        if (data.transcript && Array.isArray(data.transcript) && data.transcript.length > 0) {
            messages = data.transcript;
        }
        // Try to get from messages array
        else if (data.messages && Array.isArray(data.messages) && data.messages.length > 0) {
            messages = data.messages;
        }
        // Try to get from call.messages
        else if (data.call && data.call.messages && Array.isArray(data.call.messages)) {
            messages = data.call.messages;
            // Filter out system messages
            messages = messages.filter(msg => msg.role !== 'system');
        }
        
        // If no messages found
        if (messages.length === 0) {
            html += `
                <div class="alert alert-warning">
                    <i class="fal fa-info-circle"></i>
                    No transcript messages found for this call.
                    <br><br>
                    <button class="btn btn-sm btn-outline-secondary" onclick="debugTranscript()">
                        <i class="fal fa-bug"></i> Show Raw Data
                    </button>
                </div>
            `;
            $('#transcriptContent').html(html);
            return;
        }
        
        // Build transcript display
        html += '<div class="call-transcript">';
        
        let messageCount = 0;
        messages.forEach((message, index) => {
            // Skip if message is empty
            if (!message) return;
            
            const role = message.role || message.type || 'unknown';
            const isUser = role === 'user' || role === 'customer' || role === 'human' || role === 'caller';
            const content = message.message || message.content || message.text || message.value || '';
            
            // Skip empty messages
            if (!content || content.trim() === '') return;
            
            // Skip system messages (usually instructions)
            if (role === 'system') return;
            
            messageCount++;
            
            const roleLabel = isUser ? '👤 Customer' : '🤖 Assistant';
            const timeStr = message.timestamp ? '• ' + formatTimestamp(message.timestamp) : '';
            const secondsFromStart = message.secondsFromStart ? '• ' + formatDuration(message.secondsFromStart) : '';
            const durationStr = message.duration ? '• ' + formatDuration(message.duration / 1000) : '';
            
            html += `
                <div class="message mb-3 p-3 rounded ${isUser ? 'message-user' : 'message-assistant'}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="message-avatar mr-2 ${isUser ? 'bg-secondary text-white' : 'bg-primary text-white'}">
                                ${isUser ? 'U' : 'A'}
                            </div>
                            <strong>${roleLabel}</strong>
                        </div>
                        <div>
                            <small class="text-muted">
                                ${messageCount}
                                ${timeStr}
                                ${secondsFromStart}
                                ${durationStr}
                            </small>
                        </div>
                    </div>
                    <p class="mb-0" style="white-space: pre-wrap;">${escapeHtml(content)}</p>
                </div>
            `;
        });
        
        html += '</div>';
        
        // If no messages were rendered
        if (messageCount === 0) {
            html = `
                <div class="alert alert-warning">
                    <i class="fal fa-info-circle"></i>
                    No valid messages found in transcript.
                </div>
            `;
        }
        
        // Summary
        if (data.summary && data.summary !== '') {
            html += '<hr><h6>📝 Summary</h6>';
            html += `<div class="p-3 bg-light rounded">${escapeHtml(data.summary)}</div>`;
        }
        

        
        // Debug button
        html += `
            <hr>
            <button class="btn btn-sm btn-outline-secondary" onclick="debugTranscript()">
                <i class="fal fa-bug"></i> Show Raw Data
            </button>
        `;
        
        $('#transcriptContent').html(html);
    }

    // Show raw data for debugging
    function showRawData() {
        if (!currentTranscriptData) return;
        
        const html = `
            <h6>Raw API Response</h6>
            <div class="debug-json">${escapeHtml(JSON.stringify(currentTranscriptData, null, 2))}</div>
            <br>
            <button class="btn btn-sm btn-primary" onclick="location.reload()">Refresh</button>
        `;
        $('#transcriptContent').html(html);
    }

    // Debug function
    function debugTranscript() {
        if (!currentTranscriptData) {
            alert('No data loaded. Please try loading a transcript first.');
            return;
        }
        showRawData();
    }

    // Format duration in seconds to MM:SS
    function formatDuration(seconds) {
        if (!seconds || seconds === 0) return '00:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    // Format timestamp
    function formatTimestamp(timestamp) {
        if (!timestamp) return '';
        try {
            const date = new Date(timestamp);
            return date.toLocaleTimeString();
        } catch(e) {
            return timestamp;
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        if (typeof text !== 'string') text = String(text);
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-hide alerts after 5 seconds
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);
        
        // Close modal on backdrop click
        $('.modal').on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                $(this).modal('hide');
            }
        });
    });
</script>
@endsection