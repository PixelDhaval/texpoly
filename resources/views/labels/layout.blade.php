<!DOCTYPE html>
<html>
<head>
    <title>Labels Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .nav-link.active {
            font-weight: bold;
        }
        
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">Texpoly Impex</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <!-- Master Data -->
                    @if(Gate::any(['users', 'labels', 'customers', 'categories', 'subcategories', 'products', 'employees', 'plants']))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Master Data
                        </a>
                        <ul class="dropdown-menu">
                            @can('users')
                            <li><a class="dropdown-item" href="{{ route('users.index') }}">Users</a></li>
                            @endcan
                            @can('labels')
                            <li><a class="dropdown-item" href="{{ route('labels.index') }}">Labels</a></li>
                            @endcan
                            @can('customers')
                            <li><a class="dropdown-item" href="{{ route('customers.index') }}">Customers</a></li>
                            @endcan
                            @can('categories')
                            <li><a class="dropdown-item" href="{{ route('categories.index') }}">Categories</a></li>
                            @endcan
                            @can('subcategories')
                            <li><a class="dropdown-item" href="{{ route('subcategories.index') }}">Subcategories</a></li>
                            @endcan
                            @can('products')
                            <li><a class="dropdown-item" href="{{ route('products.index') }}">Products</a></li>
                            @endcan
                            @can('employees')
                            <li><a class="dropdown-item" href="{{ route('employees.index') }}">Employees</a></li>
                            @endcan
                            @can('plants')
                            <li><a class="dropdown-item" href="{{ route('plants.index') }}">Plants</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endif

                    <!-- Operations -->
                    @if(Gate::any(['packinglists', 'orders', 'production', 'plant-transfer', 'repacking']))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Operations
                        </a>
                        <ul class="dropdown-menu">
                            @can('packinglists')
                            <li><a class="dropdown-item" href="{{ route('packinglists.index') }}">Packing Lists</a></li>
                            @endcan
                            @can('orders')
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}">Orders</a></li>
                            @endcan
                            @can('production')
                            <li><a class="dropdown-item" href="{{ route('production.index') }}">Production</a></li>
                            @endcan
                            @can('plant-transfer')
                            <li><a class="dropdown-item" href="{{ route('plant-transfer.index') }}">Plant Transfer</a></li>
                            @endcan
                            @can('repacking')
                            <li><a class="dropdown-item" href="{{ route('repacking.index') }}">Repacking</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endif

                    <!-- Records -->
                    @if(Gate::any(['bales', 'cancellations', 'product-history']))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Records
                        </a>
                        <ul class="dropdown-menu">
                            @can('bales')
                            <li><a class="dropdown-item" href="{{ route('bales.index') }}">Bales</a></li>
                            @endcan
                            @can('cancellations')
                            <li><a class="dropdown-item" href="{{ route('cancellations.index') }}">Cancellations</a></li>
                            @endcan
                            @can('product-history')
                            <li><a class="dropdown-item" href="{{ route('products.history') }}">Product History</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endif

                    <!-- Reports -->
                    @can('reports')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('reports.index') }}">Reports</a>
                    </li>
                    @endcan
                </ul>

                <!-- Right Side -->
                <ul class="navbar-nav ms-auto">
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
    
    <div class="container-fluid mt-5">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
    <div class="alert alert-danger">
        <strong>There were some problems with your input:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
        @yield('content')
    </div>

    <footer>
        <div class="container-fluid d-flex justify-content-between py-3" style="background-color: #e5e5e5;">
            <div>&copy; {{ date('Y') }} Texpoly Impex. All rights reserved.</div>
            <div>Powered by <a href="https://adsvizion.net">ADS Vizion</a></div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
