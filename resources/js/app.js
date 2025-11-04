import './bootstrap';
import InfiniteScroll from './infinite-scroll';

// Инициализация infinite scroll при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    const recipesContainer = document.querySelector('#recipes-container');
    
    if (recipesContainer) {
        new InfiniteScroll({
            container: '#recipes-container',
            loader: '#loading-indicator',
            threshold: 300
        });
    }
});
