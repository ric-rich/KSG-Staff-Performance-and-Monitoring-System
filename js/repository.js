
// Repository Section Functions
function showRepositorySection() {
    hideAllAdminSections();
    let section = document.getElementById('adminRepositorySection');
    if (!section) {
        section = createRepositorySection();
        document.querySelector('#adminDashboard .max-w-7xl').appendChild(section);
    }
    section.classList.remove('hidden');
    loadRepositoryFiles();
    loadReadyTasksForCommit();
}

async function loadReadyTasksForCommit() {
    const container = document.getElementById('repoReadyTasksList');
    if (!container) return;

    container.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading tasks...</p>';

    try {
        const result = await apiCall('api/repository.php?action=get_completed_tasks');
        if (result.status === 'success' && result.tasks) {
            if (result.tasks.length === 0) {
                container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 italic">No tasks ready for commit.</p>';
                return;
            }
            container.innerHTML = result.tasks.map(task => `
                <div class="p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded hover:shadow-sm transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white truncate" title="${task.title}">${task.title}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">User: ${task.user_name}</p>
                            <p class="text-xs text-gray-400 mt-1">Completed: ${new Date(task.completion_date).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex space-x-2">
                        <button onclick="commitTaskToRepository(${task.task_id})" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1 px-2 rounded transition-colors">
                            Commit Files
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `<p class="text-red-500 dark:text-red-400 text-xs">${result.message || 'Failed to load tasks.'}</p>`;
        }
    } catch (error) {
        container.innerHTML = `<p class="text-red-500 dark:text-red-400 text-xs">Error: ${error.message}</p>`;
    }
}

function createRepositorySection() {
    const section = document.createElement('div');
    section.id = 'adminRepositorySection';
    section.className = 'hidden';
    section.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                Document Repository
            </h2>
            
            <!-- Repository Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Items</h4>
                    <p id="repoStatTotalItems" class="text-2xl font-bold text-gray-800 dark:text-gray-100">--</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Documents</h4>
                    <p id="repoStatDocuments" class="text-2xl font-bold text-gray-800 dark:text-gray-100">--</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Archived Tasks</h4>
                    <p id="repoStatArchivedTasks" class="text-2xl font-bold text-gray-800 dark:text-gray-100">--</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Storage Used</h4>
                    <p id="repoStatStorageUsed" class="text-2xl font-bold text-gray-800 dark:text-gray-100">--</p>
                </div>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-6">Manage completed task documents and archived files</p>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- File List -->
                <div class="lg:col-span-2">
                    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">All Files</h3>
                        <div class="relative w-full sm:w-64 mt-2 sm:mt-0">
                            <input type="text" id="repoSearchInput" placeholder="Search repository..." 
                                oninput="loadRepositoryFiles(this.value)"
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-red-500">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div id="repositoryFileList" class="space-y-3 max-h-[500px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                        <p class="text-gray-500 dark:text-gray-400">Loading files...</p>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-8">
                    <!-- Tasks Ready for Commit -->
                    <div>
                         <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Tasks Ready for Commit</h3>
                         <div id="repoReadyTasksList" class="space-y-3 max-h-[300px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 text-sm">
                            <p class="text-gray-500 dark:text-gray-400">Loading tasks...</p>
                         </div>
                    </div>

                    <!-- Upload Form -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Upload New Document</h3>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                            <div class="space-y-4">
                                <div>
                                    <label for="repoFileDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Description</label>
                                    <input type="text" id="repoFileDescription" placeholder="e.g., 'Q4 Financial Report'" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="repoFileInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">File</label>
                                    <input type="file" id="repoFileInput" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 dark:file:bg-red-900 file:text-red-700 dark:file:text-red-300 hover:file:bg-red-100 dark:hover:file:bg-red-800"/>
                                </div>
                                <button onclick="uploadRepositoryFile()" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                    Upload File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    return section;
}

async function loadRepositoryFiles(search = '') {
    const fileListContainer = document.getElementById('repositoryFileList');
    if (!fileListContainer) return;
    
    // Only show loading state if it's the initial load or empty search (to avoid flickering on typing)
    if (!search && fileListContainer.innerHTML.includes('Loading')) {
         fileListContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading files...</p>';
    }

    try {
        const url = `api/admin.php?action=get_repository_files&search=${encodeURIComponent(search)}`;
        const result = await apiCall(url);
        
        if (result.status === 'success') {
            // Update Stats
            if (result.stats) {
                document.getElementById('repoStatTotalItems').textContent = result.stats.total_items;
                document.getElementById('repoStatDocuments').textContent = result.stats.documents;
                document.getElementById('repoStatArchivedTasks').textContent = result.stats.archived_tasks;
                document.getElementById('repoStatStorageUsed').textContent = result.stats.formatted_storage;
            }

            // Render Files
            if (result.files && result.files.length > 0) {
                 fileListContainer.innerHTML = result.files.map(file => `
                    <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow">
                        <div class="flex items-center flex-1 min-w-0">
                            <svg class="w-8 h-8 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate" title="${file.file_name}">${file.file_name}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">${file.description || 'No description'}</p>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    ${file.user_name ? `<span class="text-xs text-blue-600 dark:text-blue-400">User: ${file.user_name}</span>` : ''}
                                    ${file.task_title ? `<span class="text-xs text-green-600 dark:text-green-400">Task: ${file.task_title}</span>` : ''}
                                    <span class="text-xs text-gray-400 dark:text-gray-500">${new Date(file.uploaded_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <a href="api/admin.php?action=download_repository_file&file_id=${file.id}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">Download</a>
                            <button onclick="deleteRepositoryFile(${file.id})" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium">Delete</button>
                        </div>
                    </div>
                `).join('');
            } else {
                 fileListContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">No files match your search.</p>';
            }
        } else {
            fileListContainer.innerHTML = `<p class="text-red-500 dark:text-red-400">${result.message || 'Failed to load files.'}</p>`;
        }
    } catch (error) {
        fileListContainer.innerHTML = `<p class="text-red-500 dark:text-red-400">An error occurred: ${error.message}</p>`;
    }
}

async function uploadRepositoryFile() {
    const fileInput = document.getElementById('repoFileInput');
    const description = document.getElementById('repoFileDescription').value;
    const file = fileInput.files[0];

    if (!file) {
        return showErrorMessage('Please select a file to upload.');
    }

    const formData = new FormData();
    formData.append('repository_file', file);
    formData.append('description', description);

    showLoadingModal('Uploading file...');
    try {
        const result = await apiCall('api/admin.php?action=upload_repository_file', 'POST', formData);
        if (result.status === 'success') {
            showSuccessMessage('File uploaded successfully!');
            document.getElementById('repoFileInput').value = '';
            document.getElementById('repoFileDescription').value = '';
            loadRepositoryFiles();
        } else {
            throw new Error(result.message || 'Upload failed.');
        }
    } catch (error) {
        showErrorMessage(error.message);
    } finally {
        hideLoadingModal();
    }
}

function deleteRepositoryFile(fileId) {
    showConfirmDialog('Are you sure you want to delete this file from the repository? This action cannot be undone.', async () => {
        showLoadingModal('Deleting file...');
        try {
            const result = await apiCall('api/admin.php?action=delete_repository_file', 'POST', { file_id: fileId });
            if (result.status === 'success') {
                showSuccessMessage('File deleted successfully.');
                loadRepositoryFiles();
            } else {
                throw new Error(result.message || 'Deletion failed.');
            }
        } catch (error) {
            showErrorMessage(error.message);
        } finally {
            hideLoadingModal();
        }
    });
}
