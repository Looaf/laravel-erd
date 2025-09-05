<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel ERD - Entity Relationship Diagram</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #374151;
        }

        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
        }

        .controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background: white;
            color: #374151;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .btn-primary {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .loading {
            display: none;
            color: #6b7280;
        }

        .main-content {
            height: calc(100vh - 80px);
            padding: 2rem;
        }

        .erd-container {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
        }

        .placeholder-icon {
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.375rem;
            margin: 1rem;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 1rem;
            border-radius: 0.375rem;
            margin: 1rem;
        }

        .message {
            display: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laravel ERD</h1>
        <div class="controls">
            <span class="loading">Loading...</span>
            <button class="btn" onclick="refreshData()">Refresh</button>
            <button class="btn btn-primary" onclick="loadErdData()">Load ERD</button>
        </div>
    </div>

    <div class="main-content">
        <div class="erd-container">
            <div class="placeholder" id="placeholder">
                <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7c0-2.21-1.79-4-4-4H8c-2.21 0-4 1.79-4 4z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11h6m-6 4h6m-6-8h6"></path>
                </svg>
                <h3>Entity Relationship Diagram</h3>
                <p>Click "Load ERD" to visualize your database relationships</p>
            </div>

            <div class="message error-message" id="error-message"></div>
            <div class="message success-message" id="success-message"></div>
        </div>
    </div>

    <!-- Hidden form for CSRF token refresh -->
    <form id="refresh-form" style="display: none;" method="POST" action="{{ route(config('erd.route.name', 'erd') . '.refresh') }}">
        @csrf
    </form>

    <script>
        // Set up CSRF token for AJAX requests
        function getCSRFToken() {
            // Try to get from meta tag first
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                return metaToken.getAttribute('content');
            }

            // Fallback to hidden form token
            const formToken = document.querySelector('#refresh-form input[name="_token"]');
            if (formToken) {
                return formToken.value;
            }

            return null;
        }

        const csrfToken = getCSRFToken();
        console.log('CSRF Token:', csrfToken);

        function showLoading(show = true) {
            const loading = document.querySelector('.loading');
            loading.style.display = show ? 'inline' : 'none';
        }

        function showMessage(message, type = 'error') {
            const errorEl = document.getElementById('error-message');
            const successEl = document.getElementById('success-message');

            // Hide all messages first
            errorEl.style.display = 'none';
            successEl.style.display = 'none';

            // Show the appropriate message
            if (type === 'error') {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
            } else {
                successEl.textContent = message;
                successEl.style.display = 'block';
            }

            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(() => {
                    successEl.style.display = 'none';
                }, 3000);
            }
        }

        function hideMessages() {
            document.getElementById('error-message').style.display = 'none';
            document.getElementById('success-message').style.display = 'none';
        }

        async function loadErdData() {
            showLoading(true);
            hideMessages();

            try {
                const response = await fetch('{{ route(config("erd.route.name", "erd") . ".data") }}', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to load ERD data');
                }

                if (result.success) {
                    displayErdData(result.data);
                    showMessage('ERD data loaded successfully!', 'success');
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }

            } catch (error) {
                console.error('Error loading ERD data:', error);
                showMessage('Error: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        async function refreshData() {
            showLoading(true);
            hideMessages();

            const token = getCSRFToken();
            if (!token) {
                showMessage('CSRF token not found. Please refresh the page.');
                showLoading(false);
                return;
            }

            try {
                // Create form data with CSRF token
                const formData = new FormData();
                formData.append('_token', token);

                const response = await fetch('{{ route(config("erd.route.name", "erd") . ".refresh") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    console.error('Response not OK:', response.status, result);
                    throw new Error(result.message || `HTTP ${response.status}: Failed to refresh ERD data`);
                }

                if (result.success) {
                    displayErdData(result.data);
                    showMessage('ERD data refreshed successfully!', 'success');
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }

            } catch (error) {
                console.error('Error refreshing ERD data:', error);
                showMessage('Error: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        function displayErdData(data) {
            const placeholder = document.getElementById('placeholder');

            if (!data || !data.tables || data.tables.length === 0) {
                placeholder.innerHTML = `
                    <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h3>No Models Found</h3>
                    <p>${data.metadata?.message || 'No Eloquent models were found in your application.'}</p>
                `;
                return;
            }

            // Simple display of ERD data (this would be replaced with a proper diagram library)
            let html = '<div style="padding: 2rem; overflow-y: auto; height: 100%;">';
            html += '<h3 style="margin-bottom: 1rem;">Database Tables</h3>';

            data.tables.forEach(table => {
                html += `
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 1rem; margin-bottom: 1rem;">
                        <h4 style="font-weight: 600; margin-bottom: 0.5rem;">${table.name}</h4>
                        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Model: ${table.model}</p>
                        <div style="font-size: 0.875rem;">
                            <strong>Columns:</strong>
                            <ul style="margin-left: 1rem; margin-top: 0.25rem;">
                `;

                table.columns.forEach(column => {
                    html += `<li>${column.name} (${column.type})</li>`;
                });

                html += `
                            </ul>
                        </div>
                    </div>
                `;
            });

            if (data.relationships && data.relationships.length > 0) {
                html += '<h3 style="margin: 2rem 0 1rem 0;">Relationships</h3>';
                data.relationships.forEach(rel => {
                    html += `
                        <div style="background: #eff6ff; border: 1px solid #dbeafe; border-radius: 0.375rem; padding: 1rem; margin-bottom: 1rem;">
                            <strong>${rel.label}</strong><br>
                            <span style="font-size: 0.875rem; color: #6b7280;">
                                ${rel.source} → ${rel.target} (${rel.type})
                            </span>
                        </div>
                    `;
                });
            }

            html += '</div>';
            placeholder.innerHTML = html;
        }

        // Auto-load ERD data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadErdData();
        });
    </script>
</body>

</html>