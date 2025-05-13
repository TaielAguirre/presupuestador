import json
import os
import shutil
from PIL import Image
from slugify import slugify
from datetime import datetime
from update_featured_projects import update_featured_projects

class ProjectManager:
    def __init__(self):
        self.projects_file = 'projects.json'
        self.image_dir = 'images/projects'
        self.template_dir = 'templates'

    def add_project(self, project_data, images):
        """Añade un nuevo proyecto con sus imágenes."""
        # Generar ID único basado en el título
        project_id = slugify(project_data['title'])
        
        # Crear directorio para las imágenes del proyecto
        project_image_dir = os.path.join(self.image_dir, project_id)
        os.makedirs(project_image_dir, exist_ok=True)

        # Procesar y guardar imágenes
        image_paths = self._process_images(images, project_image_dir)
        
        # Preparar datos del proyecto
        project = {
            'id': project_id,
            'title': project_data['title'],
            'category': project_data['category'],
            'featured': project_data.get('featured', False),
            'mainImage': image_paths[0] if image_paths else '',
            'description': project_data['description'],
            'technologies': project_data['technologies'],
            'githubUrl': project_data['githubUrl'],
            'liveUrl': project_data.get('liveUrl', ''),
            'images': image_paths,
            'details': project_data['details'],
            'dateAdded': datetime.now().isoformat()
        }

        # Actualizar projects.json
        self._update_projects_json(project)
        
        # Generar página HTML del proyecto
        self._generate_project_page(project)

        # Si el proyecto es destacado, actualizar la sección en index.html
        if project['featured']:
            try:
                update_featured_projects()
                print("Sección de proyectos destacados actualizada en index.html")
            except Exception as e:
                print(f"Advertencia: No se pudo actualizar la sección de proyectos destacados: {str(e)}")

        return project_id

    def _process_images(self, images, output_dir):
        """Procesa y optimiza las imágenes del proyecto."""
        image_paths = []
        for i, img_path in enumerate(images):
            # Abrir imagen
            with Image.open(img_path) as img:
                # Redimensionar si es necesario
                if img.size[0] > 1920:
                    ratio = 1920 / img.size[0]
                    new_size = (1920, int(img.size[1] * ratio))
                    img = img.resize(new_size, Image.LANCZOS)
                
                # Guardar imagen optimizada
                filename = f'image_{i}.jpg'
                output_path = os.path.join(output_dir, filename)
                img.save(output_path, 'JPEG', quality=85, optimize=True)
                
                # Guardar ruta relativa
                image_paths.append(os.path.join('images/projects', os.path.basename(output_dir), filename))
        
        return image_paths

    def _update_projects_json(self, new_project):
        """Actualiza el archivo projects.json con el nuevo proyecto."""
        try:
            with open(self.projects_file, 'r', encoding='utf-8') as f:
                data = json.load(f)
        except FileNotFoundError:
            data = {'projects': []}

        data['projects'].append(new_project)
        
        with open(self.projects_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)

    def _generate_project_page(self, project):
        """Genera la página HTML del proyecto usando una plantilla."""
        template_path = os.path.join(self.template_dir, 'project_template.html')
        
        try:
            with open(template_path, 'r', encoding='utf-8') as f:
                template = f.read()
        except FileNotFoundError:
            print("Template no encontrado. Usando template por defecto.")
            template = self._get_default_template()

        # Reemplazar placeholders en el template
        html_content = template.format(
            title=project['title'],
            description=project['description'],
            details=project['details'],
            technologies=', '.join(project['technologies']),
            github_url=project['githubUrl'],
            live_url=project['liveUrl'],
            main_image=project['mainImage'],
            images=project['images']
        )

        # Guardar archivo HTML
        output_path = f"projects/{project['id']}.html"
        os.makedirs('projects', exist_ok=True)
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(html_content)

    def _get_default_template(self):
        """Retorna un template HTML por defecto para proyectos."""
        return '''
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <link rel="stylesheet" href="../project-styles.css">
</head>
<body>
    <article class="project-detail">
        <h1>{title}</h1>
        <img src="../{main_image}" alt="{title}" class="main-image">
        
        <section class="project-info">
            <h2>Descripción</h2>
            <p>{description}</p>
            
            <h2>Detalles</h2>
            <p>{details}</p>
            
            <h2>Tecnologías</h2>
            <p class="technologies">{technologies}</p>
            
            <div class="project-links">
                <a href="{github_url}" target="_blank" class="button">Ver en GitHub</a>
                {live_url_button}
            </div>
        </section>
        
        <section class="project-gallery">
            <h2>Galería</h2>
            <div class="gallery-grid">
                {gallery_images}
            </div>
        </section>
    </article>
    <script src="../project-loader.js"></script>
</body>
</html>
        ''' 