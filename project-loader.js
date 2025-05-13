/**
 * Project Loader - Carga dinámica de proyectos para el portafolio
 * 
 * Este script carga proyectos desde un archivo JSON y/o localStorage
 * con funcionalidad de filtrado y visualización de detalles.
 */

// Módulo para cargar y mostrar proyectos
document.addEventListener('DOMContentLoaded', function() {
    // Función para cargar proyectos
    async function loadProjects() {
        try {
            // Intentar obtener proyectos desde localStorage primero
            const storedProjects = localStorage.getItem('portfolio_projects');
            let projects = [];
            
            // Si hay proyectos en localStorage, utilizarlos
            if (storedProjects) {
                projects = JSON.parse(storedProjects);
            } else {
                // Si no hay proyectos en localStorage, obtenerlos del JSON
                const response = await fetch('projects.json');
                if (!response.ok) {
                    throw new Error('No se pudo cargar el archivo de proyectos');
                }
                projects = await response.json();
                
                // Almacenar proyectos en localStorage para uso futuro
                localStorage.setItem('portfolio_projects', JSON.stringify(projects));
            }
            
            // Filtrar proyectos ocultos
            const visibleProjects = projects.filter(project => project.visible !== false);
            
            // Renderizar proyectos
            renderProjects(visibleProjects);
            
            // Inicializar filtrado por categorías
            initializeFilters(visibleProjects);
            
        } catch (error) {
            console.error('Error al cargar proyectos:', error);
            document.getElementById('portfolio-projects').innerHTML = `
                <div class="error-message">
                    <p>No se pudieron cargar los proyectos. Intenta recargar la página.</p>
                </div>
            `;
        }
    }
    
    // Función para renderizar proyectos
    function renderProjects(projects) {
        const container = document.getElementById('portfolio-projects');
        if (!container) return;
        
        // Limpiar contenedor
        container.innerHTML = '';
        
        // Si no hay proyectos, mostrar mensaje
        if (projects.length === 0) {
            container.innerHTML = '<p class="no-projects">No hay proyectos disponibles en este momento.</p>';
            return;
        }
        
        // Crear elementos para cada proyecto
        projects.forEach(project => {
            const projectElement = document.createElement('div');
            projectElement.className = `project-card${project.featured ? ' featured' : ''}`;
            projectElement.dataset.projectId = project.id;
            
            // Generar etiquetas para tecnologías
            const techTags = project.technologies.map(tech => 
                `<span class="tech-tag">${tech}</span>`
            ).join('');
            
            // Generar enlaces
            const githubLink = project.githubUrl ? 
                `<a href="${project.githubUrl}" class="project-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-github"></i> GitHub</a>` : '';
            
            const liveLink = project.liveUrl ? 
                `<a href="${project.liveUrl}" class="project-link" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt"></i> Demo</a>` : '';
            
            projectElement.innerHTML = `
                <div class="project-image">
                    <img src="${project.mainImage}" alt="${project.title}">
                    <div class="project-overlay">
                        <div class="project-overlay-content">
                            <h4>${project.title}</h4>
                            <div class="project-links">
                                ${githubLink}
                                ${liveLink}
                                <a href="#" class="project-link view-details" data-id="${project.id}">
                                    <i class="fas fa-info-circle"></i> Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="project-info">
                    <h3 class="project-title">${project.title}</h3>
                    <p class="project-description">${project.description}</p>
                    <div class="project-tech">
                        ${techTags}
                    </div>
                    <div class="project-meta">
                        <span class="project-views"><i class="fas fa-eye"></i> ${project.views || 0}</span>
                    </div>
                </div>
            `;
            
            // Agregar evento para abrir modal de detalles
            projectElement.querySelector('.view-details').addEventListener('click', function(e) {
                e.preventDefault();
                openProjectDetails(project.id);
                
                // Incrementar contador de vistas
                incrementProjectViews(project.id);
            });
            
            container.appendChild(projectElement);
        });
    }
    
    // Función para inicializar filtros
    function initializeFilters(projects) {
        // Obtener todas las categorías únicas
        const categories = new Set();
        projects.forEach(project => {
            project.category.forEach(cat => categories.add(cat));
        });
        
        // Agregar filtros al DOM
        const filterContainer = document.getElementById('portfolio-filters');
        if (!filterContainer) return;
        
        filterContainer.innerHTML = '<button class="filter-btn active" data-filter="all">Todos</button>';
        
        categories.forEach(category => {
            const btn = document.createElement('button');
            btn.className = 'filter-btn';
            btn.setAttribute('data-filter', category);
            btn.textContent = category;
            filterContainer.appendChild(btn);
        });
        
        // Eventos para filtrado
        filterContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('filter-btn')) {
                const filter = e.target.getAttribute('data-filter');
                
                // Actualizar botones activos
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');
                
                // Filtrar proyectos
                const filteredProjects = filter === 'all' ? 
                    projects : 
                    projects.filter(project => project.category.includes(filter));
                
                renderProjects(filteredProjects);
            }
        });
    }
    
    // Función para abrir detalles del proyecto
    function openProjectDetails(projectId) {
        // Obtener proyectos de localStorage
        const projects = JSON.parse(localStorage.getItem('portfolio_projects') || '[]');
        const project = projects.find(p => p.id === projectId);
        
        if (!project) return;
        
        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'project-modal';
        
        // Generar galería de imágenes
        let imagesHTML = '';
        if (project.images && project.images.length > 0) {
            imagesHTML = `
                <div class="project-gallery">
                    ${project.images.map(img => `
                        <div class="gallery-item">
                            <img src="${img}" alt="${project.title}">
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        // Generar etiquetas para tecnologías
        const techTags = project.technologies.map(tech => 
            `<span class="tech-tag">${tech}</span>`
        ).join('');
        
        // Generar enlaces
        const githubLink = project.githubUrl ? 
            `<a href="${project.githubUrl}" class="btn-outline" target="_blank" rel="noopener noreferrer"><i class="fab fa-github"></i> Ver en GitHub</a>` : '';
        
        const liveLink = project.liveUrl ? 
            `<a href="${project.liveUrl}" class="btn-primary" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt"></i> Ver Demo</a>` : '';
        
        modal.innerHTML = `
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <button class="modal-close"><i class="fas fa-times"></i></button>
                
                <div class="modal-header">
                    <h2>${project.title}</h2>
                </div>
                
                <div class="modal-body">
                    <div class="project-main-image">
                        <img src="${project.mainImage}" alt="${project.title}">
                    </div>
                    
                    ${imagesHTML}
                    
                    <div class="project-details">
                        <h3>Descripción</h3>
                        <p>${project.description}</p>
                        
                        <div class="project-details-content">
                            ${project.details ? `<p>${project.details}</p>` : ''}
                        </div>
                        
                        <h3>Tecnologías</h3>
                        <div class="project-tech">
                            ${techTags}
                        </div>
                        
                        <div class="project-actions">
                            ${githubLink}
                            ${liveLink}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al DOM
        document.body.appendChild(modal);
        
        // Prevenir scroll en el body
        document.body.style.overflow = 'hidden';
        
        // Mostrar modal con animación
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // Evento para cerrar modal
        const closeModal = () => {
            modal.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(modal);
                document.body.style.overflow = '';
            }, 300);
        };
        
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.modal-backdrop').addEventListener('click', closeModal);
    }
    
    // Función para incrementar contador de vistas
    function incrementProjectViews(projectId) {
        const projects = JSON.parse(localStorage.getItem('portfolio_projects') || '[]');
        const projectIndex = projects.findIndex(p => p.id === projectId);
        
        if (projectIndex !== -1) {
            // Incrementar vistas
            projects[projectIndex].views = (projects[projectIndex].views || 0) + 1;
            
            // Actualizar en localStorage
            localStorage.setItem('portfolio_projects', JSON.stringify(projects));
            
            // Actualizar contador en la interfaz
            const viewCounter = document.querySelector(`[data-project-id="${projectId}"] .project-views`);
            if (viewCounter) {
                viewCounter.innerHTML = `<i class="fas fa-eye"></i> ${projects[projectIndex].views}`;
            }
        }
    }
    
    // Inicializar carga de proyectos
    if (document.getElementById('portfolio-projects')) {
        loadProjects();
    }
});

// Inicializar proyectos en la página
function initProjects(projects) {
  const container = document.querySelector('.projects-grid');
  if (!container) return;
  
  // Limpiar contenedor
  container.innerHTML = '';
  
  // Ordenar proyectos (destacados primero, luego por fecha/id)
  projects.sort((a, b) => {
    if (a.featured && !b.featured) return -1;
    if (!a.featured && b.featured) return 1;
    
    // Usar el ID como proxy para la fecha (asumiendo que IDs más recientes son mayores)
    const idA = a.id.toString();
    const idB = b.id.toString();
    return idB.localeCompare(idA);
  });
  
  // Añadir proyectos al contenedor
  projects.forEach(project => {
    const projectCard = createProjectCard(project);
    container.appendChild(projectCard);
  });
  
  // Inicializar modal de detalles
  initProjectModal();
}

// Crear tarjeta de proyecto HTML
function createProjectCard(project) {
  const article = document.createElement('article');
  article.className = `project-card ${project.featured ? 'featured' : ''}`;
  article.dataset.category = project.category.join(' ');
  article.dataset.id = project.id;
  
  // Crear el contenido HTML de la tarjeta
  article.innerHTML = `
    <div class="project-image">
      <img src="${project.mainImage}" alt="${project.title}">
      <div class="project-overlay">
        <div class="project-links">
          <a href="#" class="btn-details" data-project="${project.id}">
            <i class="fas fa-images"></i> Ver más
          </a>
          ${project.githubUrl ? `
            <a href="${project.githubUrl}" class="btn-github" target="_blank">
              <i class="fab fa-github"></i> Código
            </a>
          ` : ''}
          ${project.liveUrl ? `
            <a href="${project.liveUrl}" class="btn-demo" target="_blank">
              <i class="fas fa-globe"></i> Demo
            </a>
          ` : ''}
        </div>
      </div>
    </div>
    <div class="project-info">
      ${project.featured ? '<div class="project-badge">Destacado</div>' : ''}
      <h3>${project.title}</h3>
      <p>${project.description}</p>
      <div class="tech-stack">
        ${project.technologies.map(tech => `<span>${tech}</span>`).join('')}
      </div>
    </div>
  `;
  
  // Añadir evento para mostrar detalles
  article.querySelector('.btn-details').addEventListener('click', (e) => {
    e.preventDefault();
    showProjectDetails(project.id);
  });
  
  return article;
}

// Inicializar filtros de proyectos
function initFilters() {
  const filterButtons = document.querySelectorAll('.filter-btn');
  
  filterButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Actualizar botones activos
      filterButtons.forEach(btn => btn.classList.remove('active'));
      button.classList.add('active');
      
      // Filtrar proyectos
      const filter = button.dataset.filter;
      filterProjects(filter);
    });
  });
}

// Filtrar proyectos por categoría
function filterProjects(filter) {
  const projects = document.querySelectorAll('.project-card');
  
  projects.forEach(project => {
    if (filter === 'all' || project.dataset.category.includes(filter)) {
      project.style.display = 'block';
    } else {
      project.style.display = 'none';
    }
  });
}

// Inicializar modal de detalles
function initProjectModal() {
  // Asegurarse de que existe el modal
  if (!document.getElementById('projectModal')) {
    const modal = document.createElement('div');
    modal.id = 'projectModal';
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content">
        <span class="close">&times;</span>
        <div class="project-detail-content">
          <div class="project-gallery" id="projectGallery"></div>
          <div class="project-detail-info" id="projectInfo"></div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    
    // Evento para cerrar modal
    const closeBtn = modal.querySelector('.close');
    closeBtn.onclick = () => {
      modal.style.display = 'none';
    };
    
    // Cerrar modal al hacer clic fuera
    window.onclick = (event) => {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    };
  }
}

// Mostrar detalles de un proyecto
async function showProjectDetails(projectId) {
  try {
    // Usar datos ya cargados si están disponibles
    let project;
    if (window.projectsData) {
      project = window.projectsData.find(p => p.id === projectId);
    }
    
    // Si no se encuentra el proyecto, intentar cargar los datos de nuevo
    if (!project) {
      // Intentar cargar desde JSON
      try {
        const response = await fetch('projects.json');
        if (response.ok) {
          const data = await response.json();
          if (data && data.projects) {
            project = data.projects.find(p => p.id === projectId);
          }
        }
      } catch (error) {
        console.warn('No se pudo cargar desde JSON:', error);
      }
      
      // Intentar cargar desde localStorage
      if (!project) {
        const localProjects = localStorage.getItem('portfolio-projects');
        if (localProjects) {
          const parsedProjects = JSON.parse(localProjects);
          project = parsedProjects.find(p => p.id === projectId);
        }
      }
    }
    
    if (!project) {
      console.error(`Proyecto con ID ${projectId} no encontrado`);
      return;
    }
    
    // Actualizar el modal con los detalles del proyecto
    const modal = document.getElementById('projectModal');
    const gallery = document.getElementById('projectGallery');
    const info = document.getElementById('projectInfo');
    
    // Actualizar galería de imágenes
    gallery.innerHTML = `
      <div class="main-image">
        <img src="${project.mainImage}" alt="${project.title}">
      </div>
      <div class="image-thumbnails">
        ${project.images && project.images.length > 0 ? project.images.map(img => 
          `<img src="${img}" alt="${project.title}" class="thumbnail">`
        ).join('') : ''}
      </div>
    `;
    
    // Actualizar información
    info.innerHTML = `
      <h2>${project.title}</h2>
      <div class="project-detail-description">
        <p>${project.details || project.description}</p>
      </div>
      <div class="project-tech-stack">
        <h3>Tecnologías utilizadas</h3>
        <div class="tech-tags">
          ${project.technologies.map(tech => `<span class="tech-tag">${tech}</span>`).join('')}
        </div>
      </div>
      <div class="project-actions">
        ${project.githubUrl ? `
          <a href="${project.githubUrl}" class="btn-primary" target="_blank">
            <i class="fab fa-github"></i> Ver código fuente
          </a>
        ` : ''}
        ${project.liveUrl ? `
          <a href="${project.liveUrl}" class="btn-secondary" target="_blank">
            <i class="fas fa-external-link-alt"></i> Ver demo
          </a>
        ` : ''}
      </div>
    `;
    
    // Añadir eventos a las miniaturas
    const thumbnails = gallery.querySelectorAll('.thumbnail');
    const mainImage = gallery.querySelector('.main-image img');
    
    thumbnails.forEach(thumb => {
      thumb.addEventListener('click', () => {
        mainImage.src = thumb.src;
      });
    });
    
    // Mostrar modal
    modal.style.display = 'block';
    
  } catch (error) {
    console.error('Error al mostrar detalles del proyecto:', error);
  }
}

// Función para cargar un nuevo proyecto (puede ser usada desde un formulario)
function addNewProject(projectData) {
  // Validar datos mínimos
  if (!projectData.title || !projectData.description) {
    console.error('Datos de proyecto incompletos');
    return false;
  }
  
  // Asignar un ID único si no tiene
  if (!projectData.id) {
    projectData.id = 'proj-' + Date.now();
  }
  
  // Si ya hay datos cargados, añadir al cache
  if (window.projectsData) {
    window.projectsData.push(projectData);
  }
  
  // Actualizar la UI
  const container = document.querySelector('.projects-grid');
  if (container) {
    // Eliminar mensaje de "no hay proyectos" si existe
    const emptyMessage = container.querySelector('.empty-projects');
    if (emptyMessage) {
      emptyMessage.remove();
    }
    
    // Añadir la nueva tarjeta al inicio si es destacado, al final si no
    const projectCard = createProjectCard(projectData);
    if (projectData.featured) {
      container.prepend(projectCard);
    } else {
      container.appendChild(projectCard);
    }
  }
  
  // Guardar en localStorage
  try {
    let localProjects = localStorage.getItem('portfolio-projects');
    localProjects = localProjects ? JSON.parse(localProjects) : [];
    localProjects.push(projectData);
    localStorage.setItem('portfolio-projects', JSON.stringify(localProjects));
  } catch (error) {
    console.error('Error al guardar en localStorage:', error);
  }
  
  return true;
}

// Función para eliminar un proyecto
function deleteProject(projectId) {
  // Eliminar del DOM
  const projectCard = document.querySelector(`.project-card[data-id="${projectId}"]`);
  if (projectCard) {
    projectCard.remove();
  }
  
  // Eliminar del cache
  if (window.projectsData) {
    window.projectsData = window.projectsData.filter(p => p.id !== projectId);
  }
  
  // Eliminar de localStorage
  try {
    let localProjects = localStorage.getItem('portfolio-projects');
    if (localProjects) {
      localProjects = JSON.parse(localProjects);
      localProjects = localProjects.filter(p => p.id !== projectId);
      localStorage.setItem('portfolio-projects', JSON.stringify(localProjects));
    }
  } catch (error) {
    console.error('Error al eliminar de localStorage:', error);
  }
  
  return true;
}

// Exportar funciones para uso externo
window.ProjectLoader = {
  showProjectDetails,
  addNewProject,
  deleteProject
}; 