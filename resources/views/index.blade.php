<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeknikHub File Manager</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $bsVersion = config('teknikhub-file-manager.bootstrap_version', 5);
    @endphp

    @if($bsVersion == 4)
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    @endif

    <link rel="stylesheet" href="{{ asset('vendor/teknikhub/file-manager/css/file-manager.css') }}">
    <script>
        window.TeknikHubFileManagerConfig = {
            baseUrl: '{{ route("teknikhub.file-manager.index") }}',
            disk: '{{ $disk }}',
            bootstrapVersion: {{ $bsVersion }}
        };
    </script>
</head>
<body class="bg-light">

    <div class="container-fluid py-4 h-100">
        <div class="card h-100 shadow-sm file-manager-container">
            <x-teknikhub-file-manager :disk="$disk" />
        </div>
    </div>

    @if($bsVersion == 4)
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    @else
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @endif

    <script src="{{ asset('vendor/teknikhub/file-manager/js/file-manager.js') }}"></script>
</body>
</html>
