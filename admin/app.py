from flask import Flask, request, jsonify, send_from_directory
from werkzeug.utils import secure_filename
import os
import json
from datetime import datetime
from slugify import slugify
from PIL import Image

app = Flask(__name__)

# Configuración
UPLOAD_FOLDER = '../images/projects'
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}
PROJECTS_FILE = '../projects.json'

app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max-limit

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def load_projects():
    try:
        with open(PROJECTS_FILE, 'r', encoding='utf-8') as f:
            return json.load(f)
    except FileNotFoundError:
        return {'projects': []}

def save_projects(data):
    with open(PROJECTS_FILE, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

@app.route('/')
def serve_admin():
    return send_from_directory('.', 'admin.html')

@app.route('/admin-styles.css')
def serve_styles():
    return send_from_directory('.', 'admin-styles.css')

@app.route('/admin.js')
def serve_js():
    return send_from_directory('.', 'admin.js')

@app.route('/api/projects', methods=['GET'])
def get_projects():
    return jsonify(load_projects())

@app.route('/api/projects/<project_id>', methods=['GET'])
def get_project(project_id):
    projects = load_projects()
    project = next((p for p in projects['projects'] if p['id'] == project_id), None)
    if project:
        return jsonify(project)
    return jsonify({'error': 'Proyecto no encontrado'}), 404

@app.route('/api/projects', methods=['POST'])
def create_project():
    try:
        project_data = json.loads(request.form['projectData'])
        project_id = slugify(project_data['title'])
        
        # Crear directorio para las imágenes del proyecto
        project_dir = os.path.join(app.config['UPLOAD_FOLDER'], project_id)
        os.makedirs(project_dir, exist_ok=True)
        
        # Procesar imágenes
        images = request.files.getlist('images')
        image_paths = []
        
        for i, image in enumerate(images):
            if image and allowed_file(image.filename):
                filename = f'image_{i}.jpg'
                filepath = os.path.join(project_dir, filename)
                
                # Guardar y optimizar imagen
                with Image.open(image) as img:
                    if img.size[0] > 1920:
                        ratio = 1920 / img.size[0]
                        new_size = (1920, int(img.size[1] * ratio))
                        img = img.resize(new_size, Image.LANCZOS)
                    img.save(filepath, 'JPEG', quality=85, optimize=True)
                
                image_paths.append(os.path.join('images/projects', project_id, filename))
        
        # Crear objeto de proyecto
        project = {
            'id': project_id,
            'title': project_data['title'],
            'description': project_data['description'],
            'details': project_data['details'],
            'githubUrl': project_data['githubUrl'],
            'technologies': project_data['technologies'],
            'featured': project_data.get('featured', False),
            'mainImage': image_paths[0] if image_paths else '',
            'images': image_paths,
            'dateAdded': datetime.now().isoformat(),
        }
        
        if 'liveUrl' in project_data:
            project['liveUrl'] = project_data['liveUrl']
        
        # Actualizar projects.json
        projects = load_projects()
        projects['projects'].append(project)
        save_projects(projects)
        
        return jsonify({'message': 'Proyecto creado exitosamente', 'project': project})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/projects/<project_id>', methods=['PUT'])
def update_project(project_id):
    try:
        projects = load_projects()
        project_index = next((i for i, p in enumerate(projects['projects']) if p['id'] == project_id), None)
        
        if project_index is None:
            return jsonify({'error': 'Proyecto no encontrado'}), 404
        
        project_data = json.loads(request.form['projectData'])
        existing_project = projects['projects'][project_index]
        
        # Actualizar datos básicos
        existing_project.update({
            'title': project_data['title'],
            'description': project_data['description'],
            'details': project_data['details'],
            'githubUrl': project_data['githubUrl'],
            'technologies': project_data['technologies'],
            'featured': project_data.get('featured', False),
        })
        
        if 'liveUrl' in project_data:
            existing_project['liveUrl'] = project_data['liveUrl']
        
        # Procesar nuevas imágenes si las hay
        images = request.files.getlist('images')
        if images and images[0].filename:
            project_dir = os.path.join(app.config['UPLOAD_FOLDER'], project_id)
            os.makedirs(project_dir, exist_ok=True)
            
            # Eliminar imágenes anteriores
            for old_image in existing_project['images']:
                try:
                    os.remove(os.path.join('..', old_image))
                except:
                    pass
            
            image_paths = []
            for i, image in enumerate(images):
                if image and allowed_file(image.filename):
                    filename = f'image_{i}.jpg'
                    filepath = os.path.join(project_dir, filename)
                    
                    with Image.open(image) as img:
                        if img.size[0] > 1920:
                            ratio = 1920 / img.size[0]
                            new_size = (1920, int(img.size[1] * ratio))
                            img = img.resize(new_size, Image.LANCZOS)
                        img.save(filepath, 'JPEG', quality=85, optimize=True)
                    
                    image_paths.append(os.path.join('images/projects', project_id, filename))
            
            existing_project['images'] = image_paths
            existing_project['mainImage'] = image_paths[0] if image_paths else existing_project['mainImage']
        
        save_projects(projects)
        return jsonify({'message': 'Proyecto actualizado exitosamente', 'project': existing_project})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/projects/<project_id>', methods=['DELETE'])
def delete_project(project_id):
    try:
        projects = load_projects()
        project_index = next((i for i, p in enumerate(projects['projects']) if p['id'] == project_id), None)
        
        if project_index is None:
            return jsonify({'error': 'Proyecto no encontrado'}), 404
        
        # Eliminar imágenes
        project = projects['projects'][project_index]
        project_dir = os.path.join(app.config['UPLOAD_FOLDER'], project_id)
        if os.path.exists(project_dir):
            for image in project['images']:
                try:
                    os.remove(os.path.join('..', image))
                except:
                    pass
            os.rmdir(project_dir)
        
        # Eliminar proyecto del JSON
        projects['projects'].pop(project_index)
        save_projects(projects)
        
        return jsonify({'message': 'Proyecto eliminado exitosamente'})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    os.makedirs(UPLOAD_FOLDER, exist_ok=True)
    app.run(debug=True, port=5000) 