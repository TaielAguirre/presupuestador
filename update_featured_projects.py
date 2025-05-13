import json
import os
from bs4 import BeautifulSoup

def generate_project_card(project):
    """Genera el HTML para una tarjeta de proyecto."""
    return f'''
    <div class="project-card animate__animated animate__fadeInUp">
        <div class="project-image">
            <img src="{project['mainImage']}" alt="{project['title']}" loading="lazy">
        </div>
        <div class="project-content">
            <h3>{project['title']}</h3>
            <p>{project['description']}</p>
            <div class="project-tech">
                {' '.join(f'<span class="tech-tag">{tech}</span>' for tech in project['technologies'])}
            </div>
            <div class="project-links">
                <a href="{project['githubUrl']}" class="btn-github" target="_blank">
                    <i class="fab fa-github"></i> Ver en GitHub
                </a>
                {f'<a href="{project["liveUrl"]}" class="btn-live" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Demo</a>' if project.get('liveUrl') else ''}
                <a href="projects/{project['id']}.html" class="btn-details">
                    <i class="fas fa-info-circle"></i> Más Detalles
                </a>
            </div>
        </div>
    </div>
    '''

def update_featured_projects():
    """Actualiza la sección de proyectos destacados en index.html."""
    # Leer projects.json
    with open('projects.json', 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Filtrar proyectos destacados
    featured_projects = [p for p in data['projects'] if p.get('featured', False)]
    
    # Leer index.html
    with open('index.html', 'r', encoding='utf-8') as f:
        soup = BeautifulSoup(f.read(), 'html.parser')
    
    # Encontrar o crear la sección de proyectos destacados
    featured_section = soup.find('section', {'id': 'featured-projects'})
    if not featured_section:
        # Si no existe la sección, crearla después de la sección hero
        hero_section = soup.find('section', {'class': 'hero-section'})
        if hero_section:
            featured_section = soup.new_tag('section', id='featured-projects', **{'class': 'featured-projects-section'})
            hero_section.insert_after(featured_section)
    
    # Generar el contenido de la sección
    featured_section.clear()
    
    # Añadir título de la sección
    title = soup.new_tag('h2', **{'class': 'section-title'})
    title.string = 'Proyectos Destacados'
    featured_section.append(title)
    
    # Contenedor de proyectos
    projects_container = soup.new_tag('div', **{'class': 'projects-grid'})
    
    # Añadir cada proyecto destacado
    for project in featured_projects:
        card_html = generate_project_card(project)
        card_soup = BeautifulSoup(card_html, 'html.parser')
        projects_container.append(card_soup)
    
    featured_section.append(projects_container)
    
    # Guardar los cambios
    with open('index.html', 'w', encoding='utf-8') as f:
        f.write(str(soup.prettify()))

if __name__ == '__main__':
    update_featured_projects() 