<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Set the base URL for all relative paths -->
  <base href="/PROJECTS/well/FINAL/" />
  <title>User Dashboard - Staff Performance System</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- External Styles -->
  <link rel="stylesheet" href="styles.css" />
  <script src="js/theme.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">

  <!-- User Dashboard -->
  <div id="userDashboard" class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <button data-action="toggleUserMenu"
              class="lg:hidden mr-3 p-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
              </svg>
            </button>
            <div class="w-8 h-8 bg-green-600 rounded-lg mr-3"></div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">User Dashboard</h1>
            <span id="userWelcome" class="ml-4 text-gray-600 dark:text-gray-300 hidden sm:block"></span>
          </div>
          <div class="flex items-center space-x-4">
            <!-- Theme Toggle -->
            <button id="theme-toggle" type="button"
              class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
              <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
              </svg>
              <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z"
                  fill-rule="evenodd" clip-rule="evenodd"></path>
              </svg>
            </button>
            <button data-action="toggleUserMenu"
              class="hidden lg:flex items-center text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white transition-colors">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
              </svg>
              Menu
            </button>
            <button data-action="logout"
              class="flex items-center text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white transition-colors">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              <span class="hidden sm:block">Logout</span>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- User Sidebar Menu -->
    <div id="userSidebar"
      class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out">
      <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Navigation</h2>
        <button data-action="toggleUserMenu"
          class="p-2 rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <nav class="mt-6">
        <div class="px-3 space-y-1">
          <button onclick="showUserSection('dashboard')"
            class="user-nav-item w-full flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-green-50 dark:hover:bg-gray-700 hover:text-green-700 dark:hover:text-green-400 transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 5a2 2 0 012-2h2a2 2 0 012 2v6H8V5z"></path>
            </svg>
            Dashboard
          </button>
          <button onclick="showUserSection('profile')"
            class="user-nav-item w-full flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-green-50 dark:hover:bg-gray-700 hover:text-green-700 dark:hover:text-green-400 transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            My Profile
          </button>
          <button onclick="showUserSection('tasks')"
            class="user-nav-item w-full flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-green-50 dark:hover:bg-gray-700 hover:text-green-700 dark:hover:text-green-400 transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
              </path>
            </svg>
            My Tasks
          </button>
          <button onclick="showUserSection('reports')"
            class="user-nav-item w-full flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-green-50 dark:hover:bg-gray-700 hover:text-green-700 dark:hover:text-green-400 transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
              </path>
            </svg>
            Reports
          </button>

        </div>
      </nav>
    </div>

    <!-- User Sidebar Overlay -->
    <div id="userSidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" data-action="toggleUserMenu">
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- User Content Area -->
      <div id="userContentArea" class="mt-8 ">
        <!-- Dashboard Home -->
        <div id="userDashboardHomeSection" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
          <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">
            Welcome to Your Dashboard
          </h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            This is your personal workspace where you can manage your tasks,
            view reports, and update your profile.
          </p>
          <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-green-800">
              <strong>Account Status:</strong> Active user account with
              standard permissions.
            </p>
          </div>

          <!-- Statistics Cards -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
              <h4 class="text-sm font-semibold text-blue-700">Total Tasks</h4>
              <p class="text-2xl font-bold text-blue-800" id="totalTasks">0</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
              <h4 class="text-sm font-semibold text-yellow-700">Pending</h4>
              <p class="text-2xl font-bold text-yellow-800" id="pendingCount">0</p>
            </div>
            <div class="bg-red-50 p-4 rounded-lg border border-red-100">
              <h4 class="text-sm font-semibold text-red-700">Overdue</h4>
              <p class="text-2xl font-bold text-red-800" id="overdueCount">0</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
              <h4 class="text-sm font-semibold text-green-700">Completed (Week)</h4>
              <p class="text-2xl font-bold text-green-800" id="completedCount">0</p>
            </div>
          </div>
          <h3 class="text-xl font-bold text-gray-800 my-4">Task Status Overview</h3>
          <div class="max-w-sm mx-auto">
            <canvas id="taskStatusChart"></canvas>
          </div>
        </div>
        <!-- Profile Management Section -->
        <div id="userProfileSection" class="hidden">
          <!-- Content will be loaded by app.js -->
        </div>

        <!-- User Reports Section -->
        <div id="userReportsSection" class="hidden">
          <!-- Content will be loaded by app.js -->
        </div>



        <!-- Task Management Section -->
        <div id="userTaskManagementSection" class="hidden">
          <!-- Content will be loaded by app.js -->
        </div>
      </div>
    </div>
  </div>

  <!-- Generic Modals for UI Feedback -->
  <!-- Loading Modal -->
  <div id="loadingModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-4">
      <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
      </svg>
      <span id="loadingModalMessage" class="text-gray-700">Loading...</span>
    </div>
  </div>

  <!-- Message Modal -->
  <div id="messageModal" class="hidden fixed top-5 right-5 z-[70] p-4 rounded-lg shadow-lg text-white fade-in"
    role="alert">
    <span id="messageModalText"></span>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmModal"
    class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-40 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 w-full max-w-md">
      <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4" id="confirmModalTitle">Are you sure?</h3>
      <p class="text-gray-600 dark:text-gray-300 mb-6" id="confirmModalMessage">This action cannot be undone.</p>
      <div class="flex justify-end space-x-4">
        <button id="confirmModalCancel"
          class="px-6 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold transition-colors">
          Cancel
        </button>
        <button id="confirmModalConfirm"
          class="px-6 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition-colors">
          Confirm
        </button>
      </div>
    </div>
  </div>

  <!-- Task Details Modal Container -->
  <div id="taskDetailsModalContainer"></div>

  <!-- Create New Task Modal -->
  <div id="createTaskModal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-lg">
      <div class="flex justify-between items-start mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Create New Task</h2>
        <button onclick="closeCreateTaskModal()"
          class="text-gray-500 hover:text-gray-800 text-3xl leading-none">&times;</button>
      </div>
      <form id="createTaskForm" onsubmit="event.preventDefault(); submitNewTask();" class="space-y-4">
        <div>
          <label for="newTaskTitle" class="block text-sm font-medium text-gray-700">Task Title</label>
          <input type="text" id="newTaskTitle" required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
          <label for="newTaskDescription" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
          <textarea id="newTaskDescription" rows="3"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="newTaskDueDate" class="block text-sm font-medium text-gray-700">Due Date</label>
            <input type="date" id="newTaskDueDate" required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
          </div>
          <div>
            <label for="newTaskPriority" class="block text-sm font-medium text-gray-700">Priority</label>
            <select id="newTaskPriority"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>
        <div class="border-t pt-4 mt-6 flex justify-end space-x-3">
          <button type="button" onclick="closeCreateTaskModal()"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-colors">Cancel</button>
          <button type="submit"
            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">Create
            Task</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Profile Picture Modal -->
  <div id="profilePictureModal"
    class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md transform transition-all">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Change Profile Picture</h3>
        <button onclick="closeProfilePictureModal()"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="space-y-4">
        <div id="profilePicPreviewContainer" class="hidden flex justify-center mb-4">
          <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200 dark:border-gray-700">
            <img id="profilePicPreview" src="" class="w-full h-full object-cover">
          </div>
        </div>

        <div id="profilePicError"
          class="hidden bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded relative text-sm">
        </div>

        <div class="flex items-center justify-center w-full">
          <label for="profilePicInput"
            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
              <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
              </svg>
              <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to
                  upload</span> or drag and drop</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 2MB)</p>
            </div>
            <input id="profilePicInput" type="file" class="hidden" accept="image/*" />
          </label>
        </div>

        <div class="flex justify-end pt-4">
          <button onclick="closeProfilePictureModal()"
            class="mr-2 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Cancel</button>
          <button onclick="uploadProfilePicture()"
            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700">Save Picture</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Load and app scripts -->
  <script src="js/session.js" defer></script>
  <script src="app.js" defer></script>
</body>

</html>