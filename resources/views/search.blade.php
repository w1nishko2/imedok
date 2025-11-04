@extends('layouts.app')

@section('content')
<div class="search-page">
    <div class="container py-4">
        <!-- Поисковая строка -->
        <div class="search-header mb-4">
            <h1 class="search-title">
                <i class="bi bi-search"></i> Поиск рецептов
            </h1>
            
            <form action="{{ route('search') }}" method="GET" class="search-form-large">
                <div class="input-group">
                    <input 
                        type="text" 
                        name="q" 
                        class="form-control search-input" 
                        placeholder="Введите название рецепта, ингредиент или блюдо..." 
                        value="{{ $query }}"
                        autofocus
                    >
                    <button type="submit" class="btn btn-search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Результаты поиска -->
        @if($query)
            <div class="search-results">
                <div class="results-info mb-4">
                    <h2 class="results-count">
                        @if($totalResults > 0)
                            Найдено {{ $totalResults }} {{ $totalResults == 1 ? 'рецепт' : ($totalResults < 5 ? 'рецепта' : 'рецептов') }}
                            @if($query)
                                по запросу "<span class="query-text">{{ $query }}</span>"
                            @endif
                        @else
                            Ничего не найдено по запросу "<span class="query-text">{{ $query }}</span>"
                        @endif
                    </h2>
                    
                    @if($totalResults > 0)
                        <p class="text-muted small">
                            <i class="bi bi-info-circle"></i> 
                            Показаны рецепты с похожестью от 67%
                        </p>
                    @endif
                </div>

                @if($totalResults > 0)
                    <div class="row g-4" id="recipes-container" 
                         data-next-page="{{ isset($hasMore) && $hasMore ? route('search', ['q' => $query, 'page' => ($currentPage ?? 1) + 1]) : '' }}">
                        @include('partials.recipe-cards-search', ['recipes' => $recipes])
                    </div>

                    {{-- Индикатор загрузки --}}
                    <div id="loading-indicator" style="display: none;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-dark" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-3 text-muted">Загружаем еще результаты...</p>
                        </div>
                    </div>

                    {{-- Сообщение о том, что больше нет контента --}}
                    <div id="no-more-content" class="text-center py-4" style="display: none;">
                        <p class="text-muted">
                            <i class="bi bi-check-circle"></i>
                            Это все результаты по вашему запросу
                        </p>
                    </div>
                @else
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h3>Ничего не найдено</h3>
                        <p class="text-muted">
                            Попробуйте изменить запрос или воспользуйтесь другими ключевыми словами
                        </p>
                        
                        <div class="search-suggestions mt-4">
                            <h5>Попробуйте поискать:</h5>
                            <div class="suggestion-tags">
                                <a href="{{ route('search') }}?q=курица" class="suggestion-tag">Курица</a>
                                <a href="{{ route('search') }}?q=салат" class="suggestion-tag">Салат</a>
                                <a href="{{ route('search') }}?q=десерт" class="suggestion-tag">Десерт</a>
                                <a href="{{ route('search') }}?q=суп" class="suggestion-tag">Суп</a>
                                <a href="{{ route('search') }}?q=выпечка" class="suggestion-tag">Выпечка</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="search-empty-state">
                <div class="empty-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h3>Введите запрос для поиска</h3>
                <p class="text-muted">
                    Найдите рецепты по названию, ингредиентам или типу блюда
                </p>
            </div>
        @endif
    </div>
</div>

<style>
/* Общие стили */
.search-page {
    min-height: 60vh;
    background: #fff;
}

/* Заголовок поиска */
.search-header {
    text-align: center;
    margin-bottom: 2rem;
}

.search-title {
    font-size: 2rem;
    font-weight: 700;
    color: #000;
    margin-bottom: 1.5rem;
}

.search-title i {
    color: #666;
    margin-right: 0.5rem;
}

/* Большая форма поиска */
.search-form-large {
    max-width: 800px;
    margin: 0 auto;
}

.search-form-large .input-group {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 50px;
    overflow: hidden;
}

.search-input {
    border: 2px solid #000;
    border-right: none;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    border-radius: 50px 0 0 50px !important;
}

.search-input:focus {
    box-shadow: none;
    border-color: #333;
}

.btn-search {
    background: #000;
    color: #fff;
    border: 2px solid #000;
    padding: 1rem 2rem;
    border-radius: 0 50px 50px 0 !important;
    transition: all 0.3s;
}

.btn-search:hover {
    background: #333;
    border-color: #333;
    color: #fff;
}

.btn-search i {
    font-size: 1.2rem;
}

/* Информация о результатах */
.results-info {
    border-bottom: 2px solid #000;
    padding-bottom: 1rem;
}

.results-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: #000;
    margin-bottom: 0.5rem;
}

.query-text {
    color: #666;
    font-style: italic;
}

/* Карточки рецептов (используем стили из home.blade.php) */
.card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
}

.card-link:hover {
    text-decoration: none;
    color: inherit;
}

.custom-card {
    background: #fff;
    border: 2px solid #000;
    border-radius: 0;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 4px 4px 0 #000;
    cursor: pointer;
}

.custom-card:hover {
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0 #000;
}

.custom-card-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.custom-card-img-placeholder {
    width: 100%;
    height: 200px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #ccc;
}

.custom-card-body {
    padding: 1.25rem;
    background: #fff;
}

.custom-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #000;
    margin-bottom: 0.75rem;
}

.custom-card-text {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.custom-card-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #666;
    border-top: 1px solid #eee;
    padding-top: 0.75rem;
}

.custom-stats-left {
    display: flex;
    gap: 1rem;
}

.custom-stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.custom-stat i {
    font-size: 1rem;
}

/* Бейдж похожести */
.similarity-badge {
    background: #000;
    color: #fff;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.similarity-badge i {
    margin-right: 0.25rem;
}

/* Нет результатов */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
}

.no-results-icon {
    font-size: 5rem;
    color: #ccc;
    margin-bottom: 1.5rem;
}

.no-results h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #000;
    margin-bottom: 1rem;
}

/* Подсказки поиска */
.search-suggestions {
    max-width: 600px;
    margin: 0 auto;
}

.search-suggestions h5 {
    font-weight: 700;
    color: #000;
    margin-bottom: 1rem;
}

.suggestion-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
}

.suggestion-tag {
    background: #f0f0f0;
    color: #000;
    padding: 0.5rem 1.25rem;
    border: 2px solid #000;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.suggestion-tag:hover {
    background: #000;
    color: #fff;
}

/* Пустое состояние */
.search-empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    font-size: 6rem;
    color: #ddd;
    margin-bottom: 2rem;
}

.search-empty-state h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #000;
    margin-bottom: 1rem;
}

/* Адаптивность */
@media (max-width: 768px) {
    .search-title {
        font-size: 1.5rem;
    }
    
    .search-input {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .btn-search {
        padding: 0.75rem 1.5rem;
    }
    
    .results-count {
        font-size: 1.2rem;
    }
}
</style>
@endsection
