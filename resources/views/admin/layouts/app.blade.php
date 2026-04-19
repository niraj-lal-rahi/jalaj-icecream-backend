<!DOCTYPE html>
<html>

<head>
    <title>Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-link.active {
            background-color: #0d6efd !important;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <!-- Sidebar -->
        <div class="bg-dark text-white p-3" style="width:250px; min-height:100vh;">
            <h5>Admin Panel</h5>
            <hr>

            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active bg-primary rounded' : '' }}">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="{{ route('admin.sellers.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.sellers.*') ? 'active bg-primary rounded' : '' }}">
                        Sellers Management
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.attendance.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.attendance.*') ? 'active bg-primary rounded' : '' }}">
                        Attendance Management
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.items.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.items.*') ? 'active bg-primary rounded' : '' }}">
                        Item Management
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.sales.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.sales.*') ? 'active bg-primary rounded' : '' }}">
                        Sales Management
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.whatsapp.send-message') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.whatsapp.*') ? 'active bg-primary rounded' : '' }}">
                        <i class="bi bi-whatsapp"></i> WhatsApp Messages
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.database-dumps.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.database-dumps.*') ? 'active bg-primary rounded' : '' }}">
                        💾 Database Dumps
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.logs.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.logs.*') ? 'active bg-primary rounded' : '' }}">
                        📋 System Logs
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-danger w-100">
                            Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <!-- Content -->
        <div class="flex-fill p-4">

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
