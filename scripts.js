document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.testimonials-slider');
    
    if (slider) {
        const slides = slider.querySelectorAll('.testimonial-slide');
        const dotsContainer = document.querySelector('.testimonial-dots');
        const prevBtn = document.querySelector('.testimonial-prev');
        const nextBtn = document.querySelector('.testimonial-next');
        
        let currentSlide = 0;
        const slideCount = slides.length;
        
        // Crear puntos indicadores
        for (let i = 0; i < slideCount; i++) {
            const dot = document.createElement('span');
            dot.classList.add('testimonial-dot');
            if (i === 0) dot.classList.add('active');
            dot.setAttribute('data-slide', i);
            dotsContainer.appendChild(dot);
            
            // Evento de clic en el punto
            dot.addEventListener('click', () => {
                goToSlide(i);
            });
        }
        
        // Función para ir a una diapositiva específica
        function goToSlide(index) {
            // Validar índice
            if (index < 0) index = slideCount - 1;
            if (index >= slideCount) index = 0;
            
            // Actualizar posición del slider
            slider.style.transform = `translateX(-${index * 100}%)`;
            
            // Actualizar puntos activos
            document.querySelectorAll('.testimonial-dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            
            // Actualizar índice actual
            currentSlide = index;
        }
        
        // Eventos de botones
        prevBtn.addEventListener('click', () => {
            goToSlide(currentSlide - 1);
        });
        
        nextBtn.addEventListener('click', () => {
            goToSlide(currentSlide + 1);
        });
        
        // Cambio automático cada 5 segundos
        let interval = setInterval(() => {
            goToSlide(currentSlide + 1);
        }, 5000);
        
        // Detener cambio automático al pasar el mouse
        slider.addEventListener('mouseenter', () => {
            clearInterval(interval);
        });
        
        // Reanudar cambio automático al quitar el mouse
        slider.addEventListener('mouseleave', () => {
            interval = setInterval(() => {
                goToSlide(currentSlide + 1);
            }, 5000);
        });
        
        // Soporte para gestos táctiles
        let touchStartX = 0;
        let touchEndX = 0;
        
        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        slider.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            
            if (touchEndX < touchStartX - swipeThreshold) {
                // Deslizar a la izquierda
                goToSlide(currentSlide + 1);
            }
            
            if (touchEndX > touchStartX + swipeThreshold) {
                // Deslizar a la derecha
                goToSlide(currentSlide - 1);
            }
        }
    }
});

// Funcionalidad del modo oscuro
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('i');
    
    // Verificar si hay un tema guardado
    const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme === 'dark');
    
    // Cambiar tema al hacer clic
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme === 'dark');
    });
    
    function updateThemeIcon(isDark) {
        themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }
});

// Funcionalidad del carrusel de tecnologías
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.tech-track');
    if (track) {
        // Duplicar los elementos para crear un efecto infinito
        track.innerHTML += track.innerHTML;
        
        // Detectar hover para pausar la animación
        const carousel = document.querySelector('.tech-carousel');
        carousel.addEventListener('mouseenter', () => {
            track.style.animationPlayState = 'paused';
        });
        
        carousel.addEventListener('mouseleave', () => {
            track.style.animationPlayState = 'running';
        });
    }
});

// Funcionalidad de las pestañas de certificaciones
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remover clase activa de todos los botones
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Mostrar el contenido correspondiente
            const tabId = button.getAttribute('data-tab');
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === `${tabId}-tab`) {
                    content.classList.add('active');
                }
            });
        });
    });
});

// Funcionalidad del menú móvil
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navLinks.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        // Cerrar menú al hacer clic en un enlace
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                document.body.classList.remove('menu-open');
            });
        });
        
        // Cerrar menú al hacer scroll
        window.addEventListener('scroll', () => {
            if (navLinks.classList.contains('active')) {
                menuToggle.classList.remove('active');
                navLinks.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }
});

// Project filtering
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const projectCards = document.querySelectorAll('.project-card');
    
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                button.classList.add('active');
                
                // Get filter value
                const filterValue = button.getAttribute('data-filter');
                
                // Show/hide projects based on filter
                projectCards.forEach(card => {
                    if (filterValue === 'all') {
                        card.style.display = 'block';
                    } else {
                        const categories = card.getAttribute('data-category');
                        if (categories && categories.includes(filterValue)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
                
                // Add animation class for smooth transition
                projectCards.forEach(card => {
                    if (card.style.display === 'block') {
                        card.classList.add('fade-in');
                        setTimeout(() => {
                            card.classList.remove('fade-in');
                        }, 500);
                    }
                });
            });
        });
    }
});

// Inicialización de EmailJS
(function() {
    // Configuración de EmailJS con la clave pública
    emailjs.init("F3U3F3Z3JImboatGR");
})();

// Función para manejar el envío del formulario de contacto
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const formStatus = document.getElementById('form-status');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Mostrar estado de envío
            formStatus.textContent = "Enviando mensaje...";
            formStatus.className = "form-status sending";
            
            // Recolectar datos del formulario
            const formData = {
                name: contactForm.name.value,
                email: contactForm.email.value,
                subject: contactForm.subject.value,
                message: contactForm.message.value
            };
            
            // Enviar el correo usando EmailJS con los IDs proporcionados
            emailjs.send('service_bt492xk', 'template_y9wij1g', formData)
                .then(function(response) {
                    console.log('SUCCESS!', response.status, response.text);
                    formStatus.textContent = "¡Mensaje enviado correctamente! Te responderé lo antes posible.";
                    formStatus.className = "form-status success";
                    contactForm.reset();
                }, function(error) {
                    console.log('FAILED...', error);
                    formStatus.textContent = "Error al enviar el mensaje. Por favor, intenta de nuevo o contáctame directamente a taielaguirr@gmail.com";
                    formStatus.className = "form-status error";
                });
        });
    }
});

// Función para manejar el envío del formulario de NetLoom
document.addEventListener('DOMContentLoaded', function() {
    const netloomForm = document.getElementById('netloomForm');
    const netloomFormStatus = document.getElementById('netloom-form-status');
    
    if (netloomForm) {
        netloomForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Mostrar estado de envío
            netloomFormStatus.textContent = "Enviando solicitud...";
            netloomFormStatus.className = "form-status sending";
            
            // Recolectar datos del formulario de NetLoom
            const formData = {
                company_name: netloomForm.company_name.value,
                contact_name: netloomForm.contact_name.value,
                business_email: netloomForm.business_email.value,
                business_phone: netloomForm.business_phone.value || "No proporcionado",
                service_type: netloomForm.service_type.value,
                business_message: netloomForm.business_message.value,
                to_name: "NetLoom Solutions" // Para personalizar el correo
            };
            
            // Usar el mismo servicio pero con una plantilla específica para negocios
            emailjs.send('service_bt492xk', 'template_y9wij1g', formData)
                .then(function(response) {
                    console.log('SUCCESS!', response.status, response.text);
                    netloomFormStatus.textContent = "¡Solicitud enviada correctamente! Un especialista de NetLoom se pondrá en contacto contigo en breve.";
                    netloomFormStatus.className = "form-status success";
                    netloomForm.reset();
                }, function(error) {
                    console.log('FAILED...', error);
                    netloomFormStatus.textContent = "Error al enviar la solicitud. Por favor, intenta de nuevo o escribe directamente a info@netloom.com";
                    netloomFormStatus.className = "form-status error";
                });
        });
    }
});