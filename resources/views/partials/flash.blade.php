@php
    $messages = [];

    if ($errors->any()) {
        $messages[] = ['type' => 'error', 'text' => $errors->first()];
    }

    foreach (['error', 'success', 'warning', 'info'] as $type) {
        if (session($type)) {
            $messages[] = ['type' => $type, 'text' => session($type)];
        }
    }

    $icons = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-triangle-exclamation',
        'info' => 'fa-circle-info',
    ];
@endphp

@if (! empty($messages))
    <div class="toast-wrapper" id="toastWrapper">
        @foreach ($messages as $index => $message)
            <div class="toast {{ $message['type'] }}" data-toast data-delay="{{ 4200 + ($index * 300) }}">
                <div class="toast-content">
                    <i class="fas {{ $icons[$message['type']] ?? 'fa-circle-info' }}"></i>
                    <div>{{ $message['text'] }}</div>
                    <button type="button" class="toast-close" onclick="this.closest('.toast').classList.remove('show')">&times;</button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-toast]').forEach(function (toast, index) {
                window.setTimeout(function () {
                    toast.classList.add('show');
                }, index * 80);

                const delay = Number(toast.dataset.delay || 4200);
                window.setTimeout(function () {
                    toast.classList.remove('show');
                }, delay);
            });
        });
    </script>
@endif
