<?php
require_once __DIR__ . '/inc/maintenance_check.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Set the base URL for all relative paths -->
  <base href="/PROJECTS/well/FINAL/" />
  <title>Staff Performance Monitoring and Evaluation System</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- External Styles -->
  <link rel="stylesheet" href="styles.css" />
  <script src="js/theme.js"></script>
  <style>
    html {
      scroll-behavior: smooth;
    }

    .scroll-animate {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    .flip-card {
      perspective: 1000px;
    }

    .flip-card-inner {
      position: relative;
      width: 100%;
      height: 100%;
      transition: transform 0.6s;
      transform-style: preserve-3d;
    }

    .flip-card:hover .flip-card-inner {
      transform: rotateY(180deg);
    }

    .flip-card-front,
    .flip-card-back {
      position: absolute;
      width: 100%;
      height: 100%;
      -webkit-backface-visibility: hidden;
      /* Safari */
      backface-visibility: hidden;
    }

    .flip-card-back {
      transform: rotateY(180deg);
    }

    .scroll-animate.is-visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* Admin Auth Specific Styles */
    .admin-auth-bg {
      background: radial-gradient(circle at top right, #6366f1 0%, transparent 40%),
        radial-gradient(circle at bottom left, #ec4899 0%, transparent 40%),
        #1f2937;
      /* Dark base */
    }

    /* User Auth Specific Styles - Teal/Green Theme */
    .user-auth-bg {
      background: radial-gradient(circle at top left, #10b981 0%, transparent 40%),
        radial-gradient(circle at bottom right, #3b82f6 0%, transparent 40%),
        #111827;
      /* Dark gray base */
    }

    @keyframes blob {
      0% {
        transform: translate(0px, 0px) scale(1);
      }

      33% {
        transform: translate(30px, -50px) scale(1.1);
      }

      66% {
        transform: translate(-20px, 20px) scale(0.9);
      }

      100% {
        transform: translate(0px, 0px) scale(1);
      }
    }

    .animate-blob {
      animation: blob 7s infinite;
    }

    .animation-delay-2000 {
      animation-delay: 2s;
    }

    .animation-delay-4000 {
      animation-delay: 4s;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }

    .light-glass-card {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .input-group input {
      transition: all 0.3s ease;
    }

    .input-group input:focus {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.2);
    }

    .btn-hover-effect {
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-hover-effect:active {
      transform: scale(0.98);
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {

      /* Mobile Portrait */
      .glass-card,
      .light-glass-card {
        margin: 1rem;
        max-width: 100%;
      }

      h1 {
        font-size: 1.75rem;
      }
    }

    @media (min-width: 641px) and (max-width: 1024px) {

      /* Tablets (Portrait & Landscape) */
      .glass-card,
      .light-glass-card {
        max-width: 28rem;
      }
    }

    @media (min-width: 1025px) {
      /* Desktop */
      /* Default styles usually cover this, but specific tweaks can go here */
    }
  </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:bg-gray-900 min-h-screen">
  <!-- Main Access Screen -->
  <div id="mainScreen" class="bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header
      class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-md dark:border-b dark:border-gray-700 fixed w-full z-20">
      <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex-shrink-0 flex items-center">
            <a href="#" class="flex items-center space-x-2">
              <img src="https://sppu.ksg.ac.ke/img/kSG%20LOGOS%20%20FULL.png" alt="KSG Logo" class="h-7 md:h-9" />
              <span class="font-bold text-sm sm:text-lg md:text-xl text-gray-800 dark:text-green-700">Staff Performance
                Monitoring and Evaluation System</span>
            </a>
          </div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
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

              <!-- Home Dropdown -->
              <a href="#home"
                class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Home</a>
              <a href="#metrics"
                class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Metrics</a>
              <a href="#team"
                class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Team</a>
              <a href="#about"
                class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">About
                Us</a>
              <a href="#contact"
                class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Contact</a>
            </div>
          </div>
          <div class="-mr-2 flex md:hidden">
            <!-- Mobile menu button -->
            <button type="button" id="mobile-menu-button"
              class="bg-gray-200 inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500"
              aria-controls="mobile-menu" aria-expanded="false">
              <span class="sr-only ">Open main menu</span>
              <svg id="hamburger-icon" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg id="close-icon" class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </nav>

      <!-- Mobile menu, show/hide based on menu state. -->
      <div class="md:hidden hidden dark:bg-gray-800" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
          <a href="#home"
            class="text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white block px-3 py-2 rounded-md text-base font-medium">Home</a>
          <a href="#metrics"
            class="text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white block px-3 py-2 rounded-md text-base font-medium">Metrics</a>
          <a href="#team"
            class="text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white block px-3 py-2 rounded-md text-base font-medium">Team</a>
          <a href="#about"
            class="text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white block px-3 py-2 rounded-md text-base font-medium">About
            Us</a>
          <a href="#contact"
            class="text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white block px-3 py-2 rounded-md text-base font-medium">Contact</a>
          <div class="border-t border-gray-200 my-2"></div>
          <a href="#" onclick="event.preventDefault(); showUserAuth();"
            class="text-green-600 hover:bg-gray-200 hover:text-green-700 block px-3 py-2 rounded-md text-base font-medium">
            User Login
          </a>
          <a href="#" onclick="event.preventDefault(); showAdminAuth();"
            class="text-red-600 hover:bg-gray-200 hover:text-red-700 block px-3 py-2 rounded-md text-base font-medium">
            Admin Login
          </a>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="relative min-h-screen flex items-center justify-center overflow-hidden">
      <div class="absolute top-0 left-0 w-full h-full z-0 hidden md:block">
        <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-cover">
          <source src="Images/WhatsApp Video 2025-09-30 at 10.21.45.mp4" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      </div>
      <!-- Static background for mobile -->
      <div class="absolute top-0 left-0 w-full h-full z-0 md:hidden"
        style="background-image: url('https://sppu.ksg.ac.ke/img/slider/slider_4.jpg'); background-size: cover; background-position: center;">
      </div>
      <!-- Overlay for better text readability if content is added later -->
      <div class="absolute top-0 left-0 w-full h-full bg-black opacity-50 z-10"></div>
      <!-- You can add content here on top of the video, like a title -->
      <div class="relative z-10 text-center text-white px-4 w-full max-w-7xl mx-auto">
        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
          Kenya School of Government
        </h1>
        <p class="mt-4 text-lg sm:text-xl md:text-2xl lg:text-3xl mb-8 font-light text-gray-100">
          Security Management Institute
        </p>

        <div
          class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6 animate-fade-in-up">
          <button onclick="showUserAuth()"
            class="group relative w-full sm:w-auto px-8 py-3.5 bg-green-600/90 hover:bg-green-600 text-white font-bold rounded-full shadow-lg backdrop-blur-sm transition-all duration-300 transform hover:scale-105 hover:shadow-green-500/50 flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            Staff Access
            <div class="absolute inset-0 rounded-full border border-white/20 group-hover:border-white/40"></div>
          </button>
          <button onclick="showAdminAuth()"
            class="group relative w-full sm:w-auto px-8 py-3.5 bg-red-600/90 hover:bg-red-600 text-white font-bold rounded-full shadow-lg backdrop-blur-sm transition-all duration-300 transform hover:scale-105 hover:shadow-red-500/50 flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
              </path>
            </svg>
            Admin Access
            <div class="absolute inset-0 rounded-full border border-white/20 group-hover:border-white/40"></div>
          </button>
        </div>
      </div>
    </section>

    <!-- Performance Metrics Section -->
    <section id="metrics" class="py-20 bg-white white:bg-gray-800 scroll-animate">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 class="text-base text-indigo-600 white:text-indigo-400 font-semibold tracking-wide uppercase">
            Performance
          </h2>
          <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 white:text-white sm:text-4xl">
            Real-time Automated Metrics
          </p>
          <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500 white:text-gray-400">
            Track participant engagement and financial health with our comprehensive analytics dashboard.
          </p>
        </div>

        <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
          <div
            class="bg-gray-50 white:bg-gray-700 rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
            <div class="w-16 h-16 bg-blue-100 rounded-full mx-auto flex items-center justify-center">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.28-1.25-1.44-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.28-1.25 1.44-1.857M12 12a3 3 0 100-6 3 3 0 000 6z">
                </path>
              </svg>
            </div>
            <h3 id="metric-participants_trained_total-value"
              class="mt-4 text-2xl font-bold text-gray-900 white:text-white">...</h3>
            <p id="metric-participants_trained_total-label" class="mt-1 text-gray-600 white:text-gray-300">Loading...
            </p>
          </div>
          <div
            class="bg-gray-50 white:bg-gray-700 rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
            <div class="w-16 h-16 bg-green-100 rounded-full mx-auto flex items-center justify-center">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <h3 id="metric-revenue_generated_total-value"
              class="mt-4 text-2xl font-bold text-gray-900 white:text-white">...</h3>
            <p id="metric-revenue_generated_total-label" class="mt-1 text-gray-600 white:text-gray-300">Loading...</p>
          </div>
          <div
            class="bg-gray-50 white:bg-gray-700 rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
            <div class="w-16 h-16 bg-yellow-100 rounded-full mx-auto flex items-center justify-center">
              <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                </path>
              </svg>
            </div>
            <h3 id="metric-programs_launched-value" class="mt-4 text-2xl font-bold text-gray-900 white:text-white">...
            </h3>
            <p id="metric-programs_launched-label" class="mt-1 text-gray-600 white:text-gray-300">Loading...</p>
          </div>
          <div
            class="bg-gray-50 white:bg-gray-700 rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
            <div class="w-16 h-16 bg-purple-100 rounded-full mx-auto flex items-center justify-center">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 id="metric-participant_satisfaction-value"
              class="mt-4 text-2xl font-bold text-gray-900 white:text-white">...</h3>
            <p id="metric-participant_satisfaction-label" class="mt-1 text-gray-600 white:text-gray-300">Loading...</p>
          </div>
        </div>
      </div>
    </section>
    <!-- Quarterly Performance Breakdown Section -->
    <section class="py-20 bg-gray-50 dark:bg-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate">
          <h2 class="text-3xl leading-8 font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            Quarterly Performance Breakdown
          </h2>
          <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500 dark:text-gray-400">
            A detailed look at our performance throughout the year.
          </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden scroll-animate">
          <div class="grid grid-cols-1 md:grid-cols-2">
            <!-- Left side with chart/image -->
            <div
              class="p-8 md:p-12 bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex flex-col justify-center">
              <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
              </div>
              <h3 class="text-3xl font-bold mb-3">Driving Growth & Excellence</h3>
              <p class="text-indigo-100">
                Our quarterly metrics reflect our commitment to continuous improvement and impactful results in training
                and revenue generation.
              </p>
            </div>

            <!-- Right side with table -->
            <div class="p-8 md:p-12">
              <div class="overflow-x-auto">
                <table class="min-w-full">
                  <thead>
                    <tr>
                      <th
                        class="pb-4 text-left text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Metric</th>
                      <th
                        class="pb-4 text-right text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Q1</th>
                      <th
                        class="pb-4 text-right text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Q2</th>
                      <th
                        class="pb-4 text-right text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Q3</th>
                      <th
                        class="pb-4 text-right text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Q4</th>
                    </tr>
                  </thead>
                  <tbody id="quarterlyMetricsContainer" class="divide-y divide-gray-200 dark:ivide-gray-700">
                    <!-- Data will be loaded here by JavaScript -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Programs Offered Section -->
    <section class="bg-gray-50 py-16">
      <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Programs Offered</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Program Cards -->
          <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Prevention and Control of Violent Extremism Course</h3>
            <p class="text-gray-600 mb-2">- For Senior Officers</p>
            <p class="text-gray-600">- For Middle Level Officers</p>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Border Management Programs</h3>
            <p class="text-gray-600 mb-2">- Kenya Coordinated Border Management Program</p>
            <p class="text-gray-600">- Border Security and Control Program</p>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Leadership Programs</h3>
            <p class="text-gray-600 mb-2">- Leading Organizations in Time of Crisis</p>
            <p class="text-gray-600">- Public Sector Leadership for Chiefs and Assistant Chiefs</p>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Law Enforcement Programs</h3>
            <p class="text-gray-600 mb-2">- Senior Police Officers Management Course</p>
            <p class="text-gray-600">- Community Policing Course</p>
            <p class="text-gray-600">- Certificate in VIP Protection</p>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Specialized Programs</h3>
            <p class="text-gray-600 mb-2">- Management Course for Probation Officers</p>
            <p class="text-gray-600">- Conflict Management and Peace Building Course</p>
            <p class="text-gray-600">- Transnational Threats and Response Program</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Employee Details Section -->
    <section id="team" class="py-20 bg-gray-50 dark:bg-gray-900 scroll-animate">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 class="text-base text-indigo-600 dark:text-indigo-400 font-semibold tracking-wide uppercase">
            Meet Our Team
          </h2>
          <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500 dark:text-gray-400">
            The driving force behind our success.
          </p>
        </div>

        <div id="teamMembersContainer" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
          <!-- Dynamic Team Members will be loaded here -->
        </div>
      </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="py-20 bg-gray-50 dark:bg-gray-900 scroll-animate">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center">
          <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">About The Institute</h2>
          <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
            Shaping National Security Policy and Practice
          </p>
          <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
            The Security Management Institute (SMI-KSG) is dedicated to strengthening security capacity through
            training, research, and advisory services.
          </p>
        </div>

        <div class="mt-12 grid gap-10 md:grid-cols-2 lg:grid-cols-3">
          <!-- Vision Card -->
          <div class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition-transform duration-300">
            <div
              class="flex items-center justify-center h-12 w-12 rounded-md bg-gradient-to-r from-indigo-500 to-blue-500 text-white">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                </path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <h3 class="mt-5 text-lg font-bold text-gray-900">VISION</h3>
            <p class="mt-2 text-base text-gray-600">A leading regional Center/Hub for security management training and
              research.</p>
          </div>
          <!-- Mission Card -->
          <div class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition-transform duration-300">
            <div
              class="flex items-center justify-center h-12 w-12 rounded-md bg-gradient-to-r from-green-500 to-teal-500 text-white">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z">
                </path>
              </svg>
            </div>
            <h3 class="mt-5 text-lg font-bold text-gray-900">MISSION</h3>
            <p class="mt-2 text-base text-gray-600">To provide training, research, advisory and consultancy services for
              enhanced national and regional security.</p>
          </div>
          <!-- Core Values Card -->
          <div
            class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition-transform duration-300 md:col-span-2 lg:col-span-1">
            <div
              class="flex items-center justify-center h-12 w-12 rounded-md bg-gradient-to-r from-red-500 to-orange-500 text-white">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                </path>
              </svg>
            </div>
            <h3 class="mt-5 text-lg font-bold text-gray-900">CORE VALUES</h3>
            <ul class="mt-2 text-base text-gray-600 grid grid-cols-2 gap-x-4 gap-y-1">
              <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
                </svg>Responsiveness</li>
              <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
                </svg>Vigilance</li>
              <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
                </svg>Integrity</li>
              <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
                </svg>Excellence</li>
              <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
                </svg>Commitment</li>
              <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
                </svg>Inclusivity</li>
            </ul>
          </div>
        </div>

        <!-- Detailed Information Section -->
        <div class="mt-20">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-900 mb-4">Our History & Objective</h3>
              <p class="text-gray-600 mb-4">
                Established in October 2019, SMI-KSG was founded to provide expert training, research, and consultancy
                on security issues. Initially funded by NIWETU–USAID for a project on countering violent extremism, it
                has since grown into a premier institute for strengthening security stakeholder capacity.
              </p>
              <p class="text-gray-600">
                Our principal objective is to provide a platform for discourse and contribute to Kenya’s National
                Security Policy through evidence-based research, technical assistance, and advisory services.
              </p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-8">
              <h3 class="text-xl font-bold text-gray-900 mb-4">Key Focus Areas</h3>
              <ul class="space-y-3 text-gray-600">
                <li class="flex items-start"><svg class="w-5 h-5 mr-3 text-indigo-500 flex-shrink-0 mt-1" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg><span>Human security, Violent Extremism & Terrorism</span></li>
                <li class="flex items-start"><svg class="w-5 h-5 mr-3 text-indigo-500 flex-shrink-0 mt-1" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg><span>Conflicts Early Warning & Peacebuilding</span></li>
                <li class="flex items-start"><svg class="w-5 h-5 mr-3 text-indigo-500 flex-shrink-0 mt-1" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg><span>Transnational Security & Maritime Security</span></li>
                <li class="flex items-start"><svg class="w-5 h-5 mr-3 text-indigo-500 flex-shrink-0 mt-1" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg><span>Border Control & International Migration</span></li>
                <li class="flex items-start"><svg class="w-5 h-5 mr-3 text-indigo-500 flex-shrink-0 mt-1" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg><span>Resource-based Conflicts & Social Diversity</span></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white dark:bg-gray-800 scroll-animate">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center">
          <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Get in Touch</h2>
          <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
            We'd Love to Hear From You
          </p>
          <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
            Whether you have a question about our programs, need assistance, or just want to say hello, we are here to
            help.
          </p>
        </div>

        <div class="mt-12 bg-gray-50 dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
          <div class="grid grid-cols-1 lg:grid-cols-2">
            <!-- Contact Information -->
            <div class="p-8 lg:p-12 space-y-8">
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                  </div>
                </div>
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Our Address</h3>
                  <p class="text-gray-600 dark:text-gray-400">Security Management Institute Kenya School of Government,
                    P.O
                    BOX 23030-0064, Lower Kabete, Nairobi, Kenya</p>
                </div>
              </div>

              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                      </path>
                    </svg>
                  </div>
                </div>
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Email Us</h3>
                  <p class="text-gray-600 dark:text-gray-400">directorgeneral@ksg.ac.ke</p>
                </div>
              </div>

              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                      </path>
                    </svg>
                  </div>
                </div>
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tell</h3>
                  <p class="text-gray-600 dark:text-gray-400">+254 204 182 311</p>
                </div>
              </div>
            </div>

            <!-- Contact Form -->
            <div class="p-8 lg:p-12 bg-white dark:bg-gray-800">
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Send a Direct Message</h3>
              <form id="contactForm" class="space-y-6">
                <div>
                  <label for="contact-name" class="sr-only">Your Name</label>
                  <input type="text" name="name" id="contact-name" required placeholder="Your Name"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                  <label for="contact-email" class="sr-only">Your Email</label>
                  <input type="email" name="email" id="contact-email" required placeholder="Your Email"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                  <label for="contact-message" class="sr-only">Your Message</label>
                  <textarea name="message" id="contact-message" rows="5" required placeholder="Your Message"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none resize-none transition"></textarea>
                </div>
                <div>
                  <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105">
                    Send Message
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- User Authentication Screen -->
  <div id="userAuthScreen"
    class="hidden min-h-screen flex items-center justify-center p-4 user-auth-bg relative overflow-hidden">
    <!-- Background Abstract Shapes -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
      <div
        class="absolute top-1/4 left-1/4 w-96 h-96 bg-green-500/30 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob">
      </div>
      <div
        class="absolute top-1/3 right-1/4 w-96 h-96 bg-teal-500/30 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob animation-delay-2000">
      </div>
      <div
        class="absolute -bottom-32 left-1/3 w-96 h-96 bg-blue-500/30 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob animation-delay-4000">
      </div>
    </div>

    <div
      class="light-glass-card rounded-3xl shadow-2xl p-8 w-full max-w-[28rem] animate-fade-in-scale relative z-10 border border-white/50">
      <div class="text-center mb-8">
        <div
          class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl mx-auto mb-6 flex items-center justify-center shadow-lg transform hover:rotate-6 transition-transform duration-300">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </div>
        <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Welcome Back</h2>
        <p class="text-gray-500 text-sm mt-2 font-medium">Access your staff dashboard</p>
      </div>

      <!-- User Auth Tabs -->
      <div class="flex mb-8 bg-gray-100/80 p-1.5 rounded-xl border border-gray-200 shadow-inner">
        <button id="userLoginTab" onclick="switchUserTab('login')"
          class="flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-300 shadow-sm bg-white text-gray-800 transform scale-100 ring-1 ring-gray-200">
          Login
        </button>
        <button id="userRegisterTab" onclick="switchUserTab('register')"
          class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-white/50 transition-all duration-300">
          Register
        </button>
      </div>

      <!-- User Login Form -->
      <div id="userLoginForm" class="space-y-5">
        <div class="input-group">
          <input type="email" id="userLoginEmail" placeholder="Email Address"
            class="w-full px-5 py-4 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
        </div>
        <div class="relative input-group">
          <input type="password" id="userLoginPassword" placeholder="Password"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('userLoginPassword')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="userLoginPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div id="userLoginError" class="text-red-500 text-sm hidden bg-red-50 p-3 rounded-lg border border-red-100">
        </div>

        <button onclick="userLogin()"
          class="w-full bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl btn-hover-effect flex items-center justify-center space-x-2">
          <span>Sign In</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
          </svg>
        </button>

        <div class="text-center pt-4">
          <div class="flex items-center justify-center space-x-4 text-sm">
            <button onclick="showForgotPassword('user')" class="text-gray-500 hover:text-green-600 transition-colors">
              Forgot Password?
            </button>
            <span class="text-gray-300">|</span>
            <button onclick="backToMain()" class="text-gray-500 hover:text-gray-800 transition-colors font-medium">
              Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- User Register Form -->
      <div id="userRegisterForm" class="hidden space-y-5">
        <div class="input-group">
          <input type="text" id="userRegisterName" placeholder="Full Name"
            class="w-full px-5 py-4 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
        </div>
        <div class="input-group">
          <input type="email" id="userRegisterEmail" placeholder="Email Address"
            class="w-full px-5 py-4 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
        </div>
        <div class="relative input-group">
          <input type="password" id="userRegisterPassword" placeholder="Create Password"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('userRegisterPassword')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="userRegisterPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div class="relative input-group">
          <input type="password" id="userRegisterConfirm" placeholder="Confirm Password"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('userRegisterConfirm')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="userRegisterConfirmEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div id="userRegisterError" class="text-red-500 text-sm hidden bg-red-50 p-3 rounded-lg border border-red-100">
        </div>

        <button onclick="userRegister()"
          class="w-full bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl btn-hover-effect flex items-center justify-center space-x-2">
          <span>Create Account</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
          </svg>
        </button>

        <div class="text-center pt-4">
          <button onclick="backToMain()"
            class="text-gray-500 hover:text-gray-800 transition-colors text-sm font-medium">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Admin Authentication Screen -->
  <div id="adminAuthScreen"
    class="hidden min-h-screen flex items-center justify-center p-4 admin-auth-bg relative overflow-hidden">
    <!-- Background Abstract Shapes -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
      <div
        class="absolute top-1/4 left-1/4 w-96 h-96 bg-purple-500/30 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob">
      </div>
      <div
        class="absolute top-1/3 right-1/4 w-96 h-96 bg-pink-500/30 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob animation-delay-2000">
      </div>
      <div
        class="absolute -bottom-32 left-1/3 w-96 h-96 bg-yellow-500/30 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob animation-delay-4000">
      </div>
    </div>

    <div
      class="light-glass-card rounded-3xl shadow-2xl p-8 w-full max-w-[28rem] animate-fade-in-scale relative z-10 border border-white/50">
      <div class="text-center mb-8">
        <div
          class="w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl mx-auto mb-6 flex items-center justify-center shadow-lg transform hover:rotate-6 transition-transform duration-300">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
            </path>
          </svg>
        </div>
        <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Admin Access</h2>
        <p class="text-gray-500 text-sm mt-2 font-medium">Please enter your credentials to continue</p>
      </div>

      <!-- Admin Auth Tabs -->
      <div class="flex mb-8 bg-gray-100/80 p-1.5 rounded-xl border border-gray-200 shadow-inner">
        <button id="adminLoginTab" onclick="switchAdminTab('login')"
          class="flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-300 shadow-sm bg-white text-gray-800 transform scale-100 ring-1 ring-gray-200">
          Login
        </button>
        <button id="adminRegisterTab" onclick="switchAdminTab('register')"
          class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-white/50 transition-all duration-300">
          Register
        </button>
      </div>

      <!-- Admin Login Form -->
      <div id="adminLoginForm" class="space-y-5">
        <div class="input-group">
          <input type="email" id="adminLoginEmail" placeholder="Admin Email"
            class="w-full px-5 py-4 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
        </div>
        <div class="relative input-group">
          <input type="password" id="adminLoginPassword" placeholder="Admin Password"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('adminLoginPassword')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="adminLoginPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div class="relative input-group">
          <input type="password" id="adminLoginCode" placeholder="Access Code"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('adminLoginCode')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="adminLoginCodeEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div id="adminLoginError" class="text-red-500 text-sm hidden bg-red-50 p-3 rounded-lg border border-red-100">
        </div>

        <button onclick="adminLogin()"
          class="w-full bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl btn-hover-effect flex items-center justify-center space-x-2">
          <span>Authenticate</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
          </svg>
        </button>

        <div class="text-center pt-4">
          <div class="flex items-center justify-center space-x-4 text-sm">
            <button onclick="showForgotPassword('admin')" class="text-gray-500 hover:text-red-600 transition-colors">
              Forgot Password?
            </button>
            <span class="text-gray-300">|</span>
            <button onclick="backToMain()" class="text-gray-500 hover:text-gray-800 transition-colors font-medium">
              Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- Admin Register Form -->
      <div id="adminRegisterForm" class="hidden space-y-5">
        <div class="input-group">
          <input type="text" id="adminRegisterName" placeholder="Full Name"
            class="w-full px-5 py-4 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
        </div>
        <div class="input-group">
          <input type="email" id="adminRegisterEmail" placeholder="Admin Email"
            class="w-full px-5 py-4 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
        </div>
        <div class="relative input-group">
          <input type="password" id="adminRegisterPassword" placeholder="Create Password"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('adminRegisterPassword')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="adminRegisterPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div class="relative input-group">
          <input type="password" id="adminRegisterConfirm" placeholder="Confirm Password"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('adminRegisterConfirm')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="adminRegisterConfirmEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div class="relative input-group">
          <input type="password" id="adminRegisterCode" placeholder="System Access Code"
            class="w-full px-5 py-4 pr-12 bg-white/50 border border-gray-200 text-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none placeholder-gray-400 font-medium" />
          <button type="button" onclick="togglePassword('adminRegisterCode')"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg id="adminRegisterCodeEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div id="adminRegisterError" class="text-red-500 text-sm hidden bg-red-50 p-3 rounded-lg border border-red-100">
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start space-x-3">
          <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
            </path>
          </svg>
          <p class="text-amber-800 text-xs leading-relaxed">
            Administrator registration is restricted. You must have a valid system access code provided by the IT
            department.
          </p>
        </div>

        <button onclick="adminRegister()"
          class="w-full bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl btn-hover-effect flex items-center justify-center space-x-2">
          <span>Create Account</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
        </button>

        <div class="text-center pt-4">
          <button onclick="backToMain()"
            class="text-gray-500 hover:text-gray-800 transition-colors text-sm font-medium">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Picture Modal -->
  <div id="profilePictureModal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 w-full max-w-lg">
      <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Change Profile Picture</h3>
      <div class="space-y-4">
        <input type="file" id="profilePicInput" accept="image/png, image/jpeg, image/gif"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
        <div id="profilePicPreviewContainer"
          class="hidden w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
          <img id="profilePicPreview" src="" alt="Image preview" class="max-h-full max-w-full">
        </div>
        <div id="profilePicError" class="text-red-500 text-sm hidden"></div>
      </div>
      <div class="mt-6 flex justify-end space-x-4">
        <button onclick="closeProfilePictureModal()"
          class="px-6 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold transition-colors">
          Cancel
        </button>
        <button onclick="uploadProfilePicture()"
          class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold transition-colors">
          Save Picture
        </button>
      </div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div id="forgotPasswordModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white bg-opacity-95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
      <div class="text-center mb-6">
        <div class="w-12 h-12 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Reset Password</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mt-2">
          Enter your email to reset your password
        </p>
      </div>

      <div class="space-y-4">
        <input type="email" id="forgotPasswordEmail" placeholder="Enter your email address"
          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
        <div id="forgotPasswordError" class="text-red-500 text-sm hidden"></div>
        <div id="forgotPasswordSuccess" class="text-green-500 text-sm hidden"></div>

        <button onclick="sendResetEmail()"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
          Send Reset Link
        </button>

        <div class="text-center">
          <button onclick="closeForgotPassword()" class="text-gray-500 hover:text-gray-700 text-sm">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reset Password Modal -->
  <div id="resetPasswordModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white bg-opacity-95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
      <div class="text-center mb-6">
        <div class="w-12 h-12 bg-green-100 rounded-full mx-auto mb-4 flex items-center justify-center">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Create New Password</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mt-2">Enter your new password</p>
      </div>

      <div class="space-y-4">
        <div class="relative">
          <input type="password" id="newPassword" placeholder="New Password"
            class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" />
          <button type="button" onclick="togglePassword('newPassword')"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
            <svg id="newPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div class="relative">
          <input type="password" id="confirmNewPassword" placeholder="Confirm New Password"
            class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none" />
          <button type="button" onclick="togglePassword('confirmNewPassword')"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
            <svg id="confirmNewPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>
        </div>
        <div id="resetPasswordError" class="text-red-500 text-sm hidden"></div>

        <button onclick="resetPassword()"
          class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
          Update Password
        </button>

        <div class="text-center">
          <button onclick="closeResetPassword()" class="text-gray-500 hover:text-gray-700 text-sm">
            Cancel
          </button>
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

  <!-- User Details Modal Container -->
  <div id="userDetailsModalContainer"></div>

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

  <!-- Copyright Footer -->
  <footer class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white text-xs py-2 px-4 z-30">
    <div class="flex flex-col xs:flex-row justify-between items-center space-y-1 xs:space-y-0">
      <div class="text-center xs:text-left">
        © 2025 Kenya School of Government Security Institute. All rights
        reserved.
      </div>
      <div class="text-center sm:text-right text-gray-300">
        Powered by Staff Performance Monitoring and Evaluation System

      </div>
    </div>
  </footer>
  <!-- Add the new session script here -->
  <script src="js/session.js" defer></script>

  <script src="app.js"></script>
  <script src="js/public.js"></script>
  <script>
    // Scroll animation logic remains here, function calls moved to public.js

    document.addEventListener("DOMContentLoaded", () => {
      // Scroll Animations
      const animatedElements = document.querySelectorAll(".scroll-animate");
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add("is-visible");
            }
          });
        },
        { threshold: 0.1 }
      );

      if (animatedElements.length > 0) {
        animatedElements.forEach((el) => observer.observe(el));
      }

      // Active Nav Link Highlighting
      const sections = document.querySelectorAll("section[id]");
      const navLinks = document.querySelectorAll("header nav a");

      const navObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const id = entry.target.getAttribute("id");
              navLinks.forEach((link) => {
                link.classList.remove("text-indigo-600", "font-bold");
                link.classList.add("text-gray-600");
                if (link.getAttribute("href") === `#${id}`) {
                  link.classList.add("text-indigo-600", "font-bold");
                  link.classList.remove("text-gray-600");
                }
              });
            }
          });
        },
        { rootMargin: "-50% 0px -50% 0px" }
      );

      if (sections.length > 0) {
        sections.forEach((section) => navObserver.observe(section));
      }

      // Mobile menu toggle
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const mobileMenu = document.getElementById('mobile-menu');
      const hamburgerIcon = document.getElementById('hamburger-icon');
      const closeIcon = document.getElementById('close-icon');

      mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
        hamburgerIcon.classList.toggle('hidden');
        closeIcon.classList.toggle('hidden');
      });

      // Close mobile menu when a link is clicked
      document.querySelectorAll('#mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
          mobileMenu.classList.add('hidden');
          hamburgerIcon.classList.remove('hidden');
          closeIcon.classList.add('hidden');
        });
      });

      // Home Dropdown logic removed as buttons moved to hero section

    });
  </script>
  <script>
    (function () {
      function c() {
        var b = a.contentDocument || a.contentWindow.document;
        if (b) {
          var d = b.createElement("script");
          d.innerHTML =
            "window.__CF$cv$params={r:'97d7fa50220d0de1',t:'MTc1NzYwMjIzMC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";
          b.getElementsByTagName("head")[0].appendChild(d);
        }
      }
      if (document.body) {
        var a = document.createElement("iframe");
        a.height = 1;
        a.width = 1;
        a.style.position = "absolute";
        a.style.top = 0;
        a.style.left = 0;
        a.style.border = "none";
        a.style.visibility = "hidden";
        document.body.appendChild(a);
        if ("loading" !== document.readyState) c();
        else if (window.addEventListener)
          document.addEventListener("DOMContentLoaded", c);
        else {
          var e = document.onreadystatechange || function () { };
          document.onreadystatechange = function (b) {
            e(b);
            "loading" !== document.readyState &&
              ((document.onreadystatechange = e), c());
          };
        }
      }
    })();
  </script>
  <script>
    // Update redirect paths
    if (sessionStorage.getItem('user_type') === 'admin') {
      window.location.href = 'admin/dashboard.php';
    } else if (sessionStorage.getItem('user_type') === 'user') {
      window.location.href = 'dashboard.php';
    }
  </script>
</body>

</html>