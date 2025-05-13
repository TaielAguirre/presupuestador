// Variables globales
let currentProjectId = null;
let deleteProjectId = null;
let projects = [];

// Configuración de GitHub
const GITHUB_TOKEN = localStorage.getItem('github_token');
const GITHUB_USERNAME = localStorage.getItem('github_username');
const GITHUB_REPO = localStorage.getItem('github_repo');
const GITHUB_BRANCH = 'main';

// Elementos del DOM
const projectsList = document.getElementById('projectsList');
const projectForm = document.getElementById('projectForm');
const addEditProjectForm = document.getElementById('addEditProjectForm');
const projectsTableBody = document.getElementById('projectsTableBody');
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    checkGitHubConfig();
    loadProjects();
    setupEventListeners();
});

function setupEventListeners() {
    // Navegación
    document.getElementById('showProjects').addEventListener('click', showProjectsList);
    document.getElementById('addNewProject').addEventListener('click', showAddProjectForm);
    document.getElementById('newProjectBtn').addEventListener('click', showAddProjectForm);
    document.getElementById('cancelBtn').addEventListener('click', showProjectsList);
    document.getElementById('configureGithub').addEventListener('click', showGitHubConfig);

    // Formulario
    addEditProjectForm.addEventListener('submit', handleProjectSubmit);
    document.getElementById('projectImages').addEventListener('change', handleImagePreview);
    document.getElementById('confirmDelete').addEventListener('click', handleDeleteConfirm);
}

// Funciones de GitHub
function checkGitHubConfig() {
    if (!GITHUB_TOKEN || !GITHUB_USERNAME || !GITHUB_REPO) {
        showGitHubConfig();
    }
}

function showGitHubConfig() {
    const configHtml = `
        <div class="github-config">
            <h2>Configuración de GitHub</h2>
            <form id="githubConfigForm">
                <div class="mb-3">
                    <label class="form-label">Token de GitHub</label>
                    <input type="password" class="form-control" id="githubToken" required>
                    <small class="text-muted">Necesitas un token con permisos de repo</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Usuario de GitHub</label>
                    <input type="text" class="form-control" id="githubUsername" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Repositorio</label>
                    <input type="text" class="form-control" id="githubRepo" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Configuración</button>
            </form>
        </div>
    `;
    
    projectsList.style.display = 'none';
    projectForm.style.display = 'none';
    document.querySelector('main').insertAdjacentHTML('beforeend', configHtml);
    
    document.getElementById('githubConfigForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const token = document.getElementById('githubToken').value;
        const username = document.getElementById('githubUsername').value;
        const repo = document.getElementById('githubRepo').value;
        
        localStorage.setItem('github_token', token);
        localStorage.setItem('github_username', username);
        localStorage.setItem('github_repo', repo);
        
        location.reload();
    });
}

async function getGitHubFile(path) {
    const response = await fetch(`https://api.github.com/repos/${GITHUB_USERNAME}/${GITHUB_REPO}/contents/${path}`, {
        headers: {
            'Authorization': `token ${GITHUB_TOKEN}`,
            'Accept': 'application/vnd.github.v3+json'
        }
    });
    
    if (!response.ok) throw new Error('Error al obtener archivo de GitHub');
    
    const data = await response.json();
    return {
        content: atob(data.content),
        sha: data.sha
    };
}

async function updateGitHubFile(path, content, message, sha = null) {
    const body = {
        message,
        content: btoa(content),
        branch: GITHUB_BRANCH
    };
    
    if (sha) body.sha = sha;
    
    const response = await fetch(`https://api.github.com/repos/${GITHUB_USERNAME}/${GITHUB_REPO}/contents/${path}`, {
        method: 'PUT',
        headers: {
            'Authorization': `token ${GITHUB_TOKEN}`,
            'Accept': 'application/vnd.github.v3+json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    });
    
    if (!response.ok) throw new Error('Error al actualizar archivo en GitHub');
    return await response.json();
}

// Funciones de carga y visualización
async function loadProjects() {
    try {
        const { content } = await getGitHubFile('admin/projects.json');
        projects = JSON.parse(content).projects;
        renderProjects(projects);
    } catch (error) {
        showError('Error al cargar los proyectos');
        console.error(error);
    }
}

function renderProjects(projects) {
    projectsTableBody.innerHTML = projects.map(project => `
        <tr>
            <td>
                <img src="${project.mainImage}" alt="${project.title}">
            </td>
            <td>${project.title}</td>
            <td class="text-truncate-2">${project.description}</td>
            <td>
                <span class="badge ${project.featured ? 'bg-success' : 'bg-secondary'}">
                    ${project.featured ? 'Sí' : 'No'}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-primary" onclick="editProject('${project.id}')">
                        <i class="fas fa-edit"></i>Editar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="showDeleteConfirm('${project.id}')">
                        <i class="fas fa-trash"></i>Eliminar
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Funciones de manejo de formulario
async function handleProjectSubmit(e) {
    e.preventDefault();
    
    if (!addEditProjectForm.checkValidity()) {
        addEditProjectForm.classList.add('was-validated');
        return;
    }

    const projectData = {
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        details: document.getElementById('details').value,
        githubUrl: document.getElementById('githubUrl').value,
        technologies: document.getElementById('technologies').value.split(',').map(t => t.trim()),
        featured: document.getElementById('featured').checked,
    };

    if (document.getElementById('liveUrl').value) {
        projectData.liveUrl = document.getElementById('liveUrl').value;
    }

    try {
        // Manejar imágenes
        const imageFiles = document.getElementById('projectImages').files;
        const imagePromises = Array.from(imageFiles).map(file => uploadImage(file));
        const imagePaths = await Promise.all(imagePromises);
        
        projectData.images = imagePaths;
        projectData.mainImage = imagePaths[0] || '';
        
        // Actualizar projects.json
        const { content, sha } = await getGitHubFile('admin/projects.json');
        const projectsData = JSON.parse(content);
        
        if (currentProjectId) {
            const index = projectsData.projects.findIndex(p => p.id === currentProjectId);
            if (index !== -1) {
                projectsData.projects[index] = { ...projectsData.projects[index], ...projectData };
            }
        } else {
            projectData.id = slugify(projectData.title);
            projectData.dateAdded = new Date().toISOString();
            projectsData.projects.push(projectData);
        }
        
        await updateGitHubFile(
            'admin/projects.json',
            JSON.stringify(projectsData, null, 2),
            `${currentProjectId ? 'Update' : 'Add'} project: ${projectData.title}`,
            sha
        );

        showSuccess('Proyecto guardado exitosamente');
        showProjectsList();
        loadProjects();
    } catch (error) {
        showError('Error al guardar el proyecto');
        console.error(error);
    }
}

async function uploadImage(file) {
    const reader = new FileReader();
    return new Promise((resolve, reject) => {
        reader.onload = async () => {
            try {
                const base64Data = reader.result.split(',')[1];
                const filename = `images/projects/${Date.now()}-${file.name}`;
                
                await updateGitHubFile(
                    filename,
                    base64Data,
                    `Upload image: ${filename}`
                );
                
                resolve(filename);
            } catch (error) {
                reject(error);
            }
        };
        reader.onerror = () => reject(new Error('Error al leer el archivo'));
        reader.readAsDataURL(file);
    });
}

// Funciones de edición y eliminación
async function editProject(projectId) {
    try {
        const response = await fetch(`/api/projects/${projectId}`);
        const project = await response.json();
        
        currentProjectId = projectId;
        document.getElementById('formTitle').textContent = 'Editar Proyecto';
        
        // Llenar el formulario
        document.getElementById('title').value = project.title;
        document.getElementById('description').value = project.description;
        document.getElementById('details').value = project.details;
        document.getElementById('githubUrl').value = project.githubUrl;
        document.getElementById('technologies').value = project.technologies.join(', ');
        document.getElementById('featured').checked = project.featured;
        if (project.liveUrl) {
            document.getElementById('liveUrl').value = project.liveUrl;
        }

        // Mostrar imágenes actuales
        showExistingImages(project.images);
        
        showProjectForm();
    } catch (error) {
        showError('Error al cargar el proyecto');
    }
}

function showDeleteConfirm(projectId) {
    deleteProjectId = projectId;
    deleteModal.show();
}

async function handleDeleteConfirm() {
    try {
        const response = await fetch(`/api/projects/${deleteProjectId}`, {
            method: 'DELETE'
        });

        if (!response.ok) throw new Error('Error al eliminar el proyecto');

        deleteModal.hide();
        showSuccess('Proyecto eliminado exitosamente');
        loadProjects();
    } catch (error) {
        showError('Error al eliminar el proyecto');
    }
}

// Funciones de utilidad
function handleImagePreview(e) {
    const imagePreview = document.getElementById('imagePreview');
    imagePreview.innerHTML = '';
    
    Array.from(e.target.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewContainer = document.createElement('div');
            previewContainer.className = 'preview-image';
            previewContainer.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-image" onclick="removePreviewImage(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            imagePreview.appendChild(previewContainer);
        }
        reader.readAsDataURL(file);
    });
}

function showExistingImages(images) {
    const imagePreview = document.getElementById('imagePreview');
    imagePreview.innerHTML = images.map((image, index) => `
        <div class="preview-image">
            <img src="${image}" alt="Existing Image">
            <button type="button" class="remove-image" onclick="removeExistingImage(${index})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function removePreviewImage(index) {
    const input = document.getElementById('projectImages');
    const dt = new DataTransfer();
    const { files } = input;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) dt.items.add(files[i]);
    }
    
    input.files = dt.files;
    handleImagePreview({ target: input });
}

// Funciones de navegación
function showProjectsList() {
    projectsList.style.display = 'block';
    projectForm.style.display = 'none';
    currentProjectId = null;
    addEditProjectForm.reset();
    addEditProjectForm.classList.remove('was-validated');
}

function showProjectForm() {
    projectsList.style.display = 'none';
    projectForm.style.display = 'block';
}

function showAddProjectForm() {
    currentProjectId = null;
    document.getElementById('formTitle').textContent = 'Nuevo Proyecto';
    addEditProjectForm.reset();
    document.getElementById('imagePreview').innerHTML = '';
    showProjectForm();
}

// Funciones de notificación
function showSuccess(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "var(--success-color)",
    }).showToast();
}

function showError(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "var(--danger-color)",
    }).showToast();
} 