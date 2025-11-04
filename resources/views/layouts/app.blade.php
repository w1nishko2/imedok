<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta Tags --}}
    @if(isset($seo))
        @include('components.seo-meta', ['seo' => $seo, 'itemListSchema' => $itemListSchema ?? null])
    @else
        <title>{{ config('app.name', 'Laravel') }}</title>
    @endif

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Search form in navbar */
        .search-form-navbar {
            min-width: 250px;
            max-width: 400px;
        }
        
        .search-navbar-input {
            border: 1px solid #ced4da;
            border-right: none;
            padding: 0.5rem 0.75rem;
        }
        
        .search-navbar-input:focus {
            box-shadow: none;
            border-color: #000;
        }
        
        .btn-search-navbar {
            border: 1px solid #000;
            background: #000;
            color: #fff;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s;
        }
        
        .btn-search-navbar:hover {
            background: #333;
            border-color: #333;
            color: #fff;
        }
        
        .btn-search-navbar i {
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .search-form-navbar {
                margin: 1rem 0;
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}">
                                <i class="bi bi-house-fill"></i> Главная
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('categories.index') }}">
                                <i class="bi bi-collection-fill"></i> Категории
                            </a>
                        </li>
                        @auth
                            @if(Auth::user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.index') }}">
                                        <i class="bi bi-gear-fill"></i> Админка
                                    </a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Search Form -->
                    <form action="{{ route('search') }}" method="GET" class="d-flex mx-3 search-form-navbar">
                        <div class="input-group input-group-sm">
                            <input 
                                type="text" 
                                name="q" 
                                class="form-control search-navbar-input" 
                                placeholder="Поиск рецептов..." 
                                value="{{ request('q') }}"
                                aria-label="Поиск рецептов"
                            >
                            <button type="submit" class="btn btn-dark btn-search-navbar">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
