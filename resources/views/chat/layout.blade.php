<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat — Texpoly Impex</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrap5.3.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.min.css') }}">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/chat.js'])
</head>
<body class="bg-gray-100">

<nav class="navbar navbar-expand-md navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url('/') }}">Texpoly Impex</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                @if(Gate::any(['users', 'labels', 'customers', 'categories', 'subcategories', 'products', 'employees', 'plants']))
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Master Data</a>
                    <ul class="dropdown-menu">
                        @can('users')<li><a class="dropdown-item" href="{{ route('users.index') }}">Users</a></li>@endcan
                        @can('labels')<li><a class="dropdown-item" href="{{ route('labels.index') }}">Labels</a></li>@endcan
                        @can('customers')<li><a class="dropdown-item" href="{{ route('customers.index') }}">Customers</a></li>@endcan
                        @can('categories')<li><a class="dropdown-item" href="{{ route('categories.index') }}">Categories</a></li>@endcan
                        @can('subcategories')<li><a class="dropdown-item" href="{{ route('subcategories.index') }}">Sections</a></li>@endcan
                        @can('products')<li><a class="dropdown-item" href="{{ route('products.index') }}">Products</a></li>@endcan
                        @can('employees')<li><a class="dropdown-item" href="{{ route('employees.index') }}">Employees</a></li>@endcan
                        @can('plants')<li><a class="dropdown-item" href="{{ route('plants.index') }}">Plants</a></li>@endcan
                    </ul>
                </li>
                @endif

                @if(Gate::any(['packinglists', 'orders', 'production', 'plant-transfer', 'repacking']))
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Operations</a>
                    <ul class="dropdown-menu">
                        @can('packinglists')<li><a class="dropdown-item" href="{{ route('packinglists.index') }}">Packing Lists</a></li>@endcan
                        @can('orders')<li><a class="dropdown-item" href="{{ route('orders.index') }}">Orders</a></li>@endcan
                        @can('production')<li><a class="dropdown-item" href="{{ route('production.index') }}">Production</a></li>@endcan
                        @can('plant_transfer')<li><a class="dropdown-item" href="{{ route('plant-transfer.index') }}">Plant Transfer</a></li>@endcan
                        @can('repacking')<li><a class="dropdown-item" href="{{ route('repacking.index') }}">Repacking</a></li>@endcan
                    </ul>
                </li>
                @endif
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-2">
                    <a class="nav-link active fw-bold" href="{{ route('chat.index') }}">
                        <i class="bi bi-chat-dots-fill me-1"></i>Chat
                        <livewire:chat.notification-bell />
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div style="height: calc(100vh - 56px); overflow: hidden;">
    @yield('content')
</div>

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
@livewireScripts
@stack('scripts')
</body>
</html>
