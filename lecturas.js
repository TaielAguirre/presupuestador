document.addEventListener('DOMContentLoaded', () => {
    // Elementos del DOM
    const articleCards = document.querySelectorAll('.article-card');
    const modal = document.getElementById('articleModal');
    const modalContent = document.getElementById('articleContent');
    const closeBtn = document.querySelector('.close');
    const categoryCards = document.querySelectorAll('.category-card');
    const articlesGrid = document.querySelector('.articles-grid');
    const searchInput = document.querySelector('#searchArticles');
    const filterLevel = document.querySelector('#filterLevel');
    const timeFilter = document.querySelector('#filterTime');
    const categoryPills = document.querySelectorAll('.category-pill');
    const readingCards = document.querySelectorAll('.reading-card');

    // Datos de los artículos
    const articlesData = [
        {
            id: 'zero-trust',
            title: 'Implementando Zero Trust en la Nube',
            description: 'Una guía completa sobre la implementación de arquitectura Zero Trust en entornos cloud.',
            image: 'images/readings/zero-trust.jpg',
            readingTime: '20 min',
            level: 'Avanzado',
            category: 'Cloud Security',
            template: 'article-zero-trust'
        },
        {
            id: 'cloud-security',
            title: 'Fundamentos de Seguridad en la Nube',
            description: 'Conceptos fundamentales y mejores prácticas para asegurar infraestructuras cloud.',
            image: 'images/readings/cloud-security.jpg',
            readingTime: '15 min',
            level: 'Básico',
            category: 'Cloud Security',
            template: 'article-cloud-security'
        },
        {
            id: 'trading-algorithms',
            title: 'Algoritmos de Trading con Python',
            description: 'Desarrollo de estrategias automatizadas de trading utilizando Python y APIs financieras.',
            image: 'images/readings/trading.jpg',
            readingTime: '45 min',
            level: 'Avanzado',
            category: 'Desarrollo',
            template: 'trading_article_template'
        },
        {
            id: 'microservices',
            title: 'Arquitectura de Microservicios con Node.js',
            description: 'Guía práctica para diseñar, implementar y desplegar una arquitectura de microservicios escalable.',
            image: 'images/readings/microservices.jpg',
            readingTime: '30 min',
            level: 'Intermedio',
            category: 'Desarrollo',
            template: 'article-microservices'
        },
        {
            id: 'ci-cd',
            title: 'Implementando CI/CD con GitHub Actions',
            description: 'Tutorial paso a paso para configurar pipelines de integración y despliegue continuo usando GitHub Actions.',
            image: 'images/readings/ci-cd.jpg',
            readingTime: '25 min',
            level: 'Intermedio',
            category: 'DevOps',
            template: 'article-ci-cd'
        },
        {
            id: 'redes-seguridad',
            title: 'Fundamentos de Redes y Ciberseguridad en la Era Cloud',
            description: 'Explora los conceptos esenciales de redes informáticas y su integración con la seguridad en entornos cloud modernos.',
            image: 'images/readings/redes-seguridad.jpg',
            readingTime: '35 min',
            level: 'Intermedio',
            category: 'Cloud Security',
            template: 'article-redes-seguridad'
        }
    ];

    // Estado de los filtros activos
    const activeFilters = {
        search: '',
        level: 'all',
        time: 'all',
        category: 'all'
    };

    // Función para mostrar un artículo
    window.showArticle = function(articleId) {
        const modal = document.getElementById('articleModal');
        const modalContent = document.getElementById('articleContent');
        
        if (!modal || !modalContent) {
            console.error('Modal elements not found');
            return;
        }

        try {
            // Obtener el contenido del artículo basado en el ID
            const articleContent = getArticleContent(articleId);
            
            if (!articleContent) {
                console.error('No content found for article:', articleId);
                return;
            }

            // Actualizar el contenido del modal
            modalContent.innerHTML = articleContent;
            
            // Mostrar el modal con una pequeña demora para asegurar que el contenido se cargue
            setTimeout(() => {
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }, 100);
            
        } catch (error) {
            console.error('Error showing article:', error);
        }
    };

    // Función para obtener el contenido del artículo
    function getArticleContent(articleId) {
        try {
            const template = document.getElementById(`article-${articleId}`);
            console.log('Template found:', template ? 'yes' : 'no');
            
            if (!template) {
                console.warn(`Template not found for article: ${articleId}`);
                return `
                    <div class="article-header">
                        <h2>Artículo No Disponible</h2>
                    </div>
                    <div class="article-content">
                        <p>Lo sentimos, el artículo solicitado no está disponible en este momento.</p>
                        <p>Por favor, intenta con otro artículo o vuelve más tarde.</p>
                    </div>
                `;
            }
            
            return template.content.cloneNode(true).innerHTML;
        } catch (error) {
            console.error('Error getting article content:', error);
            return null;
        }
    }

    // Event Listeners para el modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // Función para filtrar artículos
    function filterArticles() {
        readingCards.forEach(card => {
            let visible = true;

            // Filtrar por búsqueda
            if (activeFilters.search) {
                const title = card.querySelector('.reading-title').textContent.toLowerCase();
                const description = card.querySelector('.reading-description').textContent.toLowerCase();
                const searchTerm = activeFilters.search.toLowerCase();
                visible = visible && (title.includes(searchTerm) || description.includes(searchTerm));
            }

            // Filtrar por nivel
            if (activeFilters.level !== 'all') {
                const level = card.querySelector('.meta-item:nth-child(2)').textContent.toLowerCase();
                visible = visible && level.includes(activeFilters.level.toLowerCase());
            }

            // Filtrar por tiempo
            if (activeFilters.time !== 'all') {
                const time = card.querySelector('.meta-item:nth-child(1)').textContent.toLowerCase();
                visible = visible && time.includes(activeFilters.time.toLowerCase());
            }

            // Filtrar por categoría
            if (activeFilters.category !== 'all') {
                const category = card.querySelector('.reading-category').textContent.toLowerCase();
                visible = visible && category.includes(activeFilters.category.toLowerCase());
            }

            // Aplicar visibilidad con animación
            if (visible) {
                card.style.display = 'block';
                requestAnimationFrame(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                });
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    }

    // Función para agregar al historial de lectura
    const addToReadingHistory = (articleId) => {
        let history = JSON.parse(localStorage.getItem('readingHistory') || '[]');
        if (!history.includes(articleId)) {
            history.push(articleId);
            localStorage.setItem('readingHistory', JSON.stringify(history));
            updateReadingHistory();
        }
    };

    // Función para actualizar la visualización del historial
    const updateReadingHistory = () => {
        const historyContainer = document.querySelector('#readingHistory');
        if (!historyContainer) return;

        const history = JSON.parse(localStorage.getItem('readingHistory') || '[]');
        historyContainer.innerHTML = history.length ? history.map(id => {
            const article = articlesData.find(article => article.id === id);
            return `
                <div class="history-item">
                    <h4>${article.title}</h4>
                    <span>${article.category}</span>
                </div>
            `;
        }).join('') : '<p>No hay artículos leídos recientemente</p>';
    };

    // Event Listeners
    articleCards.forEach(card => {
        card.addEventListener('click', (e) => {
            e.preventDefault();
            const articleId = e.currentTarget.dataset.article;
            showArticle(articleId);
        });
    });

    // Filtrado por categorías
    categoryCards.forEach(category => {
        category.addEventListener('click', () => {
            categoryCards.forEach(cat => cat.classList.remove('active'));
            category.classList.add('active');
            filterArticles();
        });
    });

    // Búsqueda y filtros
    searchInput?.addEventListener('input', (e) => {
        activeFilters.search = e.target.value;
        filterArticles();
    });

    filterLevel?.addEventListener('change', (e) => {
        activeFilters.level = e.target.value;
        filterArticles();
    });

    timeFilter?.addEventListener('change', (e) => {
        activeFilters.time = e.target.value;
        filterArticles();
    });

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            // Actualizar UI
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            // Actualizar filtros
            activeFilters.category = pill.dataset.category;
            filterArticles();
        });
    });

    // Animaciones al hacer scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementBottom = element.getBoundingClientRect().bottom;
            
            if (elementTop < window.innerHeight && elementBottom > 0) {
                element.classList.add('visible');
            }
        });
    };

    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll();

    // Intersection Observer para animaciones de entrada
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    // Observar las tarjetas para animaciones
    readingCards.forEach(card => {
        observer.observe(card);
    });

    // Inicialización
    updateReadingHistory();
    filterArticles();
}); 