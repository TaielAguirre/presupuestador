/**
 * Administración de proyectos
 * Este archivo contiene todas las funcionalidades para gestionar proyectos,
 * exportar/importar, analíticas y medidas de seguridad.
 */

// GESTOR DE PROYECTOS
const ProjectManager = {
    /**
     * Carga todos los proyectos almacenados en localStorage
     * @returns {Array} Array de proyectos
     */
    loadProjects: function() {
        const storedProjects = localStorage.getItem('portfolio_projects');
        return storedProjects ? JSON.parse(storedProjects) : [];
    },
    
    /**
     * Guarda proyectos en localStorage
     * @param {Array} projects - Array de proyectos a guardar
     */
    saveProjects: function(projects) {
        localStorage.setItem('portfolio_projects', JSON.stringify(projects));
        this.showNotification('Proyectos guardados correctamente', 'success');
    },
    
    /**
     * Renderiza la lista de proyectos en el panel de administración
     * @param {Array} projects - Array de proyectos a mostrar
     */
    renderProjectsList: function(projects) {
        const projectsList = document.getElementById('projects-list');
        if (!projectsList) return;
        
        // Limpiar contenido actual
        projectsList.innerHTML = '';
        
        // Si no hay proyectos, mostrar mensaje
        if (projects.length === 0) {
            projectsList.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem 1rem;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3>No hay proyectos</h3>
                    <p style="color: #777; margin-bottom: 1.5rem;">Comienza agregando tu primer proyecto</p>
                    <a href="proyecto-admin-xyz.html" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Nuevo Proyecto
                    </a>
                </div>
            `;
            return;
        }
        
        // Renderizar cada proyecto
        projects.forEach(project => {
            const projectCard = document.createElement('div');
            projectCard.className = `project-card${project.featured ? ' featured' : ''}`;
            projectCard.dataset.id = project.id;
            
            // Icono de visibilidad
            const visibilityIcon = project.visible !== false ? 
                '<i class="fas fa-eye"></i>' : 
                '<i class="fas fa-eye-slash"></i>';
            
            // Formatear tecnologías y categorías para vista
            const techList = project.technologies.slice(0, 3).map(tech => 
                `<span class="tech-tag">${tech}</span>`
            ).join('');
            
            const categoryList = project.category.slice(0, 2).map(cat => 
                `<span class="category-tag">${cat}</span>`
            ).join('');
            
            projectCard.innerHTML = `
                <div class="project-header">
                    <div class="project-title">${project.title}</div>
                    <div class="project-actions">
                        <button class="btn-edit" title="Editar proyecto">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn-visibility" title="${project.visible !== false ? 'Ocultar proyecto' : 'Mostrar proyecto'}">
                            ${visibilityIcon}
                        </button>
                        <button class="btn-delete" title="Eliminar proyecto">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="project-image">
                    <img src="${project.mainImage || 'images/placeholder-project.jpg'}" alt="${project.title}">
                </div>
                <div class="project-info">
                    <div class="project-categories">
                        ${categoryList}
                        ${project.category.length > 2 ? `<span class="category-tag">+${project.category.length - 2}</span>` : ''}
                    </div>
                    <div class="project-tech">
                        ${techList}
                        ${project.technologies.length > 3 ? `<span class="tech-tag">+${project.technologies.length - 3}</span>` : ''}
                    </div>
                    <div class="project-stats">
                        <div class="stats-item">
                            <i class="fas fa-eye"></i> ${project.views || 0}
                        </div>
                        <div class="stats-item">
                            <i class="fas fa-calendar-alt"></i> ${new Date(project.dateAdded || Date.now()).toLocaleDateString()}
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar evento para editar proyecto
            projectCard.querySelector('.btn-edit').addEventListener('click', () => {
                this.editProject(project.id);
            });
            
            // Agregar evento para cambiar visibilidad
            projectCard.querySelector('.btn-visibility').addEventListener('click', () => {
                this.toggleProjectVisibility(project.id);
            });
            
            // Agregar evento para eliminar proyecto
            projectCard.querySelector('.btn-delete').addEventListener('click', () => {
                this.deleteProject(project.id);
            });
            
            projectsList.appendChild(projectCard);
        });
    },
    
    /**
     * Edita un proyecto existente
     * @param {string} projectId - ID del proyecto a editar
     */
    editProject: function(projectId) {
        // Almacenar ID del proyecto a editar
        localStorage.setItem('editing_project_id', projectId);
        // Redireccionar a página de edición
        window.location.href = 'proyecto-admin-xyz.html';
    },
    
    /**
     * Cambia la visibilidad de un proyecto
     * @param {string} projectId - ID del proyecto a cambiar
     */
    toggleProjectVisibility: function(projectId) {
        const projects = this.loadProjects();
        const projectIndex = projects.findIndex(p => p.id === projectId);
        
        if (projectIndex !== -1) {
            // Invertir estado de visibilidad
            projects[projectIndex].visible = projects[projectIndex].visible === false ? true : false;
            
            // Guardar cambios
            this.saveProjects(projects);
            
            // Actualizar vista
            this.renderProjectsList(projects);
            
            // Mostrar notificación
            const message = projects[projectIndex].visible !== false ? 
                'Proyecto ahora es visible' : 
                'Proyecto ahora está oculto';
            this.showNotification(message, 'success');
        }
    },
    
    /**
     * Elimina un proyecto
     * @param {string} projectId - ID del proyecto a eliminar
     */
    deleteProject: function(projectId) {
        if (!confirm('¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer.')) {
            return;
        }
        
        const projects = this.loadProjects();
        const updatedProjects = projects.filter(p => p.id !== projectId);
        
        // Guardar cambios
        this.saveProjects(updatedProjects);
        
        // Actualizar vista
        this.renderProjectsList(updatedProjects);
        
        // Actualizar analíticas
        if (typeof ProjectAnalytics !== 'undefined' && ProjectAnalytics.init) {
            ProjectAnalytics.init();
        }
        
        this.showNotification('Proyecto eliminado correctamente', 'success');
    },
    
    /**
     * Reordena proyectos con arrastrar y soltar
     * (Implementación pendiente)
     */
    enableProjectReordering: function() {
        // Implementación pendiente para drag & drop
        console.log('Reordenamiento por arrastrar y soltar será implementado próximamente');
    },
    
    /**
     * Muestra una notificación
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de notificación (success, error)
     */
    showNotification: function(message, type = 'success') {
        // Verificar si ya existe una notificación
        let notification = document.querySelector('.notification');
        
        // Si no existe, crearla
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'notification';
            document.body.appendChild(notification);
        }
        
        // Configurar notificación
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.classList.add('show');
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
};

// EXPORTACIÓN/IMPORTACIÓN DE PROYECTOS
const ProjectExport = {
    /**
     * Exporta proyectos a un archivo JSON
     */
    exportProjects: function() {
        const projects = ProjectManager.loadProjects();
        
        if (projects.length === 0) {
            ProjectManager.showNotification('No hay proyectos para exportar', 'error');
            return;
        }
        
        // Crear Blob y URL para descarga
        const dataStr = JSON.stringify(projects, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        // Crear elemento de descarga
        const a = document.createElement('a');
        a.href = url;
        a.download = `portfolio_projects_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        
        // Limpiar
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100);
        
        ProjectManager.showNotification('Proyectos exportados correctamente', 'success');
    },
    
    /**
     * Importa proyectos desde un archivo JSON
     * @param {HTMLInputElement} input - Elemento input file
     */
    importProjects: function(input) {
        if (!input.files || !input.files[0]) {
            return;
        }
        
        const file = input.files[0];
        
        if (file.type !== 'application/json') {
            ProjectManager.showNotification('El archivo debe ser de tipo JSON', 'error');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                const importedProjects = JSON.parse(e.target.result);
                
                if (!Array.isArray(importedProjects)) {
                    throw new Error('Formato de archivo inválido');
                }
                
                // Validar estructura de cada proyecto
                importedProjects.forEach(project => {
                    if (!project.id || !project.title) {
                        throw new Error('Uno o más proyectos tienen un formato inválido');
                    }
                });
                
                // Preguntar si desea reemplazar o agregar
                const currentProjects = ProjectManager.loadProjects();
                
                if (currentProjects.length > 0) {
                    if (confirm('¿Deseas reemplazar tus proyectos actuales? Presiona "Cancelar" para añadirlos a los existentes.')) {
                        // Reemplazar proyectos
                        ProjectManager.saveProjects(importedProjects);
                    } else {
                        // Combinar proyectos, evitando duplicados por ID
                        const combinedProjects = [...currentProjects];
                        
                        importedProjects.forEach(importedProject => {
                            const exists = combinedProjects.findIndex(p => p.id === importedProject.id);
                            
                            if (exists !== -1) {
                                // Actualizar proyecto existente
                                combinedProjects[exists] = importedProject;
                            } else {
                                // Agregar nuevo proyecto
                                combinedProjects.push(importedProject);
                            }
                        });
                        
                        ProjectManager.saveProjects(combinedProjects);
                    }
                } else {
                    // No hay proyectos actuales, solo guardar los importados
                    ProjectManager.saveProjects(importedProjects);
                }
                
                // Actualizar vista
                ProjectManager.renderProjectsList(ProjectManager.loadProjects());
                
                // Actualizar analíticas
                if (typeof ProjectAnalytics !== 'undefined' && ProjectAnalytics.init) {
                    ProjectAnalytics.init();
                }
                
                ProjectManager.showNotification(`${importedProjects.length} proyectos importados correctamente`, 'success');
            } catch (error) {
                ProjectManager.showNotification(`Error al importar: ${error.message}`, 'error');
            }
            
            // Limpiar input
            input.value = '';
        };
        
        reader.onerror = function() {
            ProjectManager.showNotification('Error al leer el archivo', 'error');
            input.value = '';
        };
        
        reader.readAsText(file);
    }
};

// ANALÍTICAS DE PROYECTOS
const ProjectAnalytics = {
    /**
     * Inicializa y renderiza todas las analíticas
     */
    init: function() {
        this.renderStatCards();
        this.renderCategoryChart();
        this.renderTechChart();
    },
    
    /**
     * Renderiza las tarjetas de estadísticas
     */
    renderStatCards: function() {
        const projects = ProjectManager.loadProjects();
        const statsContainer = document.getElementById('projects-stats');
        
        if (!statsContainer) return;
        
        // Calcular estadísticas
        const totalProjects = projects.length;
        const totalViews = projects.reduce((sum, project) => sum + (project.views || 0), 0);
        const visibleProjects = projects.filter(p => p.visible !== false).length;
        const featuredProjects = projects.filter(p => p.featured).length;
        
        // Generar HTML para estadísticas
        statsContainer.innerHTML = `
            <div class="stats-card">
                <div class="stats-value">${totalProjects}</div>
                <div class="stats-label">Total Proyectos</div>
            </div>
            <div class="stats-card">
                <div class="stats-value">${visibleProjects}</div>
                <div class="stats-label">Proyectos Visibles</div>
            </div>
            <div class="stats-card">
                <div class="stats-value">${featuredProjects}</div>
                <div class="stats-label">Proyectos Destacados</div>
            </div>
            <div class="stats-card">
                <div class="stats-value">${totalViews}</div>
                <div class="stats-label">Vistas Totales</div>
            </div>
        `;
    },
    
    /**
     * Renderiza el gráfico de categorías
     */
    renderCategoryChart: function() {
        const projects = ProjectManager.loadProjects();
        const chartContainer = document.getElementById('category-chart');
        
        if (!chartContainer) return;
        
        // Contar categorías
        const categoryCounts = {};
        projects.forEach(project => {
            project.category.forEach(category => {
                categoryCounts[category] = (categoryCounts[category] || 0) + 1;
            });
        });
        
        // Convertir a array y ordenar
        const categoryArray = Object.entries(categoryCounts)
            .map(([name, count]) => ({ name, count }))
            .sort((a, b) => b.count - a.count);
        
        // Limitar a las 6 categorías más populares
        const topCategories = categoryArray.slice(0, 6);
        
        // Encontrar el valor máximo para calcular porcentajes
        const maxCount = Math.max(...topCategories.map(c => c.count));
        
        // Generar HTML para el gráfico
        chartContainer.innerHTML = `
            <div class="chart-title">Categorías más populares</div>
            ${topCategories.map(category => `
                <div class="chart-bar-item">
                    <div class="chart-bar-label">${category.name} (${category.count})</div>
                    <div class="chart-bar-container">
                        <div class="chart-bar" style="width: ${(category.count / maxCount) * 100}%"></div>
                        <div class="chart-bar-value">${Math.round((category.count / projects.length) * 100)}%</div>
                    </div>
                </div>
            `).join('')}
        `;
    },
    
    /**
     * Renderiza el gráfico de tecnologías
     */
    renderTechChart: function() {
        const projects = ProjectManager.loadProjects();
        const chartContainer = document.getElementById('tech-chart');
        
        if (!chartContainer) return;
        
        // Contar tecnologías
        const techCounts = {};
        projects.forEach(project => {
            project.technologies.forEach(tech => {
                techCounts[tech] = (techCounts[tech] || 0) + 1;
            });
        });
        
        // Convertir a array y ordenar
        const techArray = Object.entries(techCounts)
            .map(([name, count]) => ({ name, count }))
            .sort((a, b) => b.count - a.count);
        
        // Limitar a las 6 tecnologías más populares
        const topTech = techArray.slice(0, 6);
        
        // Encontrar el valor máximo para calcular porcentajes
        const maxCount = Math.max(...topTech.map(t => t.count));
        
        // Generar HTML para el gráfico
        chartContainer.innerHTML = `
            <div class="chart-title">Tecnologías más utilizadas</div>
            ${topTech.map(tech => `
                <div class="chart-bar-item">
                    <div class="chart-bar-label">${tech.name} (${tech.count})</div>
                    <div class="chart-bar-container">
                        <div class="chart-bar" style="width: ${(tech.count / maxCount) * 100}%"></div>
                        <div class="chart-bar-value">${Math.round((tech.count / projects.length) * 100)}%</div>
                    </div>
                </div>
            `).join('')}
        `;
    },
    
    /**
     * Incrementa contador de visitas de un proyecto
     * @param {string} projectId - ID del proyecto
     */
    incrementProjectViews: function(projectId) {
        const projects = ProjectManager.loadProjects();
        const projectIndex = projects.findIndex(p => p.id === projectId);
        
        if (projectIndex !== -1) {
            // Incrementar vistas
            projects[projectIndex].views = (projects[projectIndex].views || 0) + 1;
            
            // Guardar sin mostrar notificación
            localStorage.setItem('portfolio_projects', JSON.stringify(projects));
        }
    }
};

// SEGURIDAD
const SecurityManager = {
    /**
     * Registra un intento de inicio de sesión
     * @param {boolean} success - Si el intento fue exitoso o no
     */
    logLoginAttempt: function(success) {
        const attempts = JSON.parse(localStorage.getItem('login_attempts') || '[]');
        
        // Añadir nuevo intento
        attempts.push({
            date: Date.now(),
            success: success,
            ip: 'client-side' // Cliente no puede obtener IP real
        });
        
        // Mantener solo los últimos 10 intentos
        if (attempts.length > 10) {
            attempts.shift();
        }
        
        // Guardar intentos
        localStorage.setItem('login_attempts', JSON.stringify(attempts));
        
        // Bloquear temporalmente si hay demasiados intentos fallidos
        const recentFailedAttempts = attempts
            .filter(a => !a.success && a.date > Date.now() - 15 * 60 * 1000)
            .length;
        
        if (recentFailedAttempts >= 5) {
            localStorage.setItem('login_blocked_until', Date.now() + 30 * 60 * 1000);
        }
    },
    
    /**
     * Verifica si el inicio de sesión está bloqueado
     * @returns {boolean} Verdadero si está bloqueado
     */
    isLoginBlocked: function() {
        const blockedUntil = parseInt(localStorage.getItem('login_blocked_until') || '0');
        return blockedUntil > Date.now();
    },
    
    /**
     * Obtiene tiempo restante de bloqueo en minutos
     * @returns {number} Minutos restantes
     */
    getBlockTimeRemaining: function() {
        const blockedUntil = parseInt(localStorage.getItem('login_blocked_until') || '0');
        const remainingMs = blockedUntil - Date.now();
        return Math.ceil(remainingMs / (60 * 1000));
    }
};

// Inicializar contador de visitas para la página de visualización de proyectos
document.addEventListener('DOMContentLoaded', function() {
    // Solo en la página principal donde se muestran los proyectos
    const projectsContainer = document.getElementById('portfolio-projects');
    
    if (projectsContainer) {
        // Escuchar click en proyectos para incrementar contador
        projectsContainer.addEventListener('click', function(e) {
            const projectCard = e.target.closest('[data-project-id]');
            if (projectCard) {
                const projectId = projectCard.dataset.projectId;
                // Incrementar contador de visitas
                ProjectAnalytics.incrementProjectViews(projectId);
            }
        });
    }
}); 