import argparse
import json
import os
from project_manager import ProjectManager

def main():
    parser = argparse.ArgumentParser(description='Añadir un nuevo proyecto al portafolio')
    
    # Argumentos requeridos
    parser.add_argument('--title', required=True, help='Título del proyecto')
    parser.add_argument('--description', required=True, help='Descripción corta del proyecto')
    parser.add_argument('--details', required=True, help='Detalles completos del proyecto')
    parser.add_argument('--github', required=True, help='URL del repositorio GitHub')
    parser.add_argument('--technologies', required=True, help='Tecnologías usadas (separadas por comas)')
    parser.add_argument('--images', required=True, nargs='+', help='Rutas de las imágenes del proyecto')
    
    # Argumentos opcionales
    parser.add_argument('--live-url', help='URL del proyecto en vivo')
    parser.add_argument('--category', default=['development'], nargs='+', help='Categorías del proyecto')
    parser.add_argument('--featured', action='store_true', help='Marcar como proyecto destacado')

    args = parser.parse_args()

    # Validar que las imágenes existan
    for img_path in args.images:
        if not os.path.exists(img_path):
            print(f"Error: La imagen {img_path} no existe")
            return

    # Preparar datos del proyecto
    project_data = {
        'title': args.title,
        'description': args.description,
        'details': args.details,
        'githubUrl': args.github,
        'technologies': [tech.strip() for tech in args.technologies.split(',')],
        'category': args.category,
        'featured': args.featured,
    }

    if args.live_url:
        project_data['liveUrl'] = args.live_url

    # Crear instancia del ProjectManager y añadir proyecto
    manager = ProjectManager()
    try:
        project_id = manager.add_project(project_data, args.images)
        print(f"¡Proyecto '{args.title}' añadido exitosamente!")
        print(f"ID del proyecto: {project_id}")
    except Exception as e:
        print(f"Error al añadir el proyecto: {str(e)}")

if __name__ == '__main__':
    main() 