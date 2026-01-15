// Public facing scripts for landing page and real-time updates

document.addEventListener('DOMContentLoaded', () => {
    // Initial load of content
    loadPublicMetrics();
    loadQuarterlyMetrics();
    loadTeamMembers();

    // Refresh every 30 seconds for real-time updates
    setInterval(() => {
        loadPublicMetrics();
        loadQuarterlyMetrics();
        loadTeamMembers();
    }, 30000);
});

// Helper to get base URL
function getBaseUrl() {
    return document.baseURI;
}

// Load Main Performance Metrics
async function loadPublicMetrics() {
    try {
        const baseUrl = getBaseUrl();
        const url = new URL('api/public.php?action=get_site_metrics', baseUrl);
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.status === 'success') {
            const metrics = data.metrics;
            // Update the DOM elements with keys matching the HTML IDs
            updateMetric('participants_trained_total', metrics['participants_trained_total']);
            updateMetric('revenue_generated_total', metrics['revenue_generated_total']);
            updateMetric('programs_launched', metrics['programs_launched']);
            updateMetric('participant_satisfaction', metrics['participant_satisfaction']);
        }
    } catch (error) {
        console.error('Error loading metrics:', error);
    }
}

function updateMetric(key, metricData) {
    if (!metricData) return;
    const valueEl = document.getElementById(`metric-${key}-value`);
    const labelEl = document.getElementById(`metric-${key}-label`);
    
    if (valueEl) {
        // Prevent re-animation if value hasn't changed
        if (valueEl.textContent === metricData.value) return;

        // Check if we actually need to update (simple cache check could go here, but for now we animate)
        // Parse value and suffix/prefix
        // Regex to match a number possibly containing commas or decimals, surrounded by text
        // This regex captures: 1: Prefix, 2: Number, 3: Suffix
        const match = metricData.value.match(/^([^0-9.-]*)([\d,.]+)(.*)$/);
        
        if (match) {
            const prefix = match[1] || '';
            const numberStr = match[2].replace(/,/g, ''); // Remove commas for parsing
            const suffix = match[3] || '';
            const target = parseFloat(numberStr);
            
            if (!isNaN(target)) {
                animateValue(valueEl, 0, target, 2000, prefix, suffix, match[2].includes('.'));
            } else {
                valueEl.textContent = metricData.value;
            }
        } else {
            valueEl.textContent = metricData.value;
        }
    }
    if (labelEl) labelEl.textContent = metricData.label;
}

function animateValue(obj, start, end, duration, prefix = '', suffix = '', isFloat = false) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        
        // Easing function for smooth animation (Out Quart)
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        
        let current = start + (end - start) * easeOutQuart;
        
        // Format the number: float if needed, commas for thousands
        let formattedStr = isFloat 
            ? current.toFixed(2) // Assumes 2 decimals if float
            : Math.floor(current).toLocaleString();

        // If the original number string didn't have decimals, we might want to respect that even if current is float intermediate
        // But toFixed/floor handles that. 
        // Re-adding commas if the original had them could be complex, 
        // for now standard toLocaleString is good for integers. Note: toLocaleString might not match exact original formatting preference if mixed.
        
        obj.innerHTML = prefix + formattedStr + suffix;
        
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
             // Ensure final value matches exactly (with original formatting if possible, but here we reconstructed it)
             // Let's just set result of valid math
             let finalStr = isFloat ? end.toFixed(2) : end.toLocaleString();
             obj.innerHTML = prefix + finalStr + suffix;
        }
    };
    window.requestAnimationFrame(step);
}

// Load Quarterly Performance Breakdown
async function loadQuarterlyMetrics() {
    try {
        const baseUrl = getBaseUrl();
        const url = new URL('api/public.php?action=get_site_metrics', baseUrl);
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'success') {
            const m = data.metrics;
            const container = document.getElementById('quarterlyMetricsContainer');
            
            if (container) {
                // Safeguard against missing keys
                const getVal = (key) => m[key]?.value || '-';
                
                container.innerHTML = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="py-4 text-left text-sm font-medium text-gray-900 dark:text-white">Participants Trained</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('participants_trained_q1')}</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('participants_trained_q2')}</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('participants_trained_q3')}</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('participants_trained_q4')}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="py-4 text-left text-sm font-medium text-gray-900 dark:text-white">Revenue Generated</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('revenue_generated_q1')}</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('revenue_generated_q2')}</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('revenue_generated_q3')}</td>
                        <td class="py-4 text-right text-sm text-gray-500 dark:text-gray-400">${getVal('revenue_generated_q4')}</td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading quarterly performance:', error);
    }
}

// Load Team Members
async function loadTeamMembers() {
    try {
        const baseUrl = getBaseUrl();
        const url = new URL('api/public.php?action=get_team_members', baseUrl);
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'success') {
            const team = data.team;
            const container = document.getElementById('teamMembersContainer');
            
            if (container) {
                if (team.length === 0) {
                    container.innerHTML = '<p class="text-center text-gray-500 col-span-full py-8">No team members found.</p>';
                } else {
                    container.innerHTML = team.map(member => createTeamMemberCard(member)).join('');
                }
            }
        }
    } catch (error) {
        console.error('Error loading team members:', error);
    }
}

function createTeamMemberCard(member) {
    // Determine image source
    let imgSrc = member.profile_picture 
        ? member.profile_picture 
        : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.name) + '&background=random';

    // Badge Logic (Smaller)
    const badge = member.type === 'Admin' 
        ? '<span class="absolute top-3 right-3 px-2 py-0.5 text-[10px] font-bold text-red-600 bg-red-100/80 backdrop-blur-sm rounded-full shadow-sm z-10 border border-red-200">Admin</span>' 
        : '<span class="absolute top-3 right-3 px-2 py-0.5 text-[10px] font-bold text-blue-600 bg-blue-100/80 backdrop-blur-sm rounded-full shadow-sm z-10 border border-blue-200">Staff</span>';

    return `
        <div class="group relative bg-white dark:bg-gray-800 rounded-3xl shadow-md hover:shadow-xl transition-all duration-300 p-5 flex flex-col items-center border border-gray-100 dark:border-gray-700 overflow-hidden h-full">
            ${badge}
            
            <!-- Circular Profile Image Container with Gradient Ring (Smaller) -->
            <div class="relative w-28 h-28 mb-4 group-hover:scale-105 transition-transform duration-500">
                <!-- Decorative rotating ring on hover -->
                <div class="absolute -inset-2 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 rounded-full opacity-70 group-hover:opacity-100 blur-sm transition-opacity duration-300"></div>
                
                <!-- Main Image Circle -->
                <div class="relative w-full h-full rounded-full overflow-hidden border-[4px] border-white dark:border-gray-800 shadow-inner bg-gray-100">
                    <img class="w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-110" 
                         src="${imgSrc}" 
                         alt="${member.name}"
                         loading="lazy">
                </div>
                
                <!-- Online/Status Indicator Dot -->
                <div class="absolute bottom-2 right-2 w-3 h-3 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full shadow-sm hidden"></div>
            </div>
            
            <!-- Content -->
            <div class="text-center relative z-10 w-full">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-indigo-600 group-hover:to-pink-600 transition-all duration-300 truncate px-1">
                    ${member.name}
                </h3>
                
                <div class="inline-block relative">
                     <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 truncate max-w-[140px] mx-auto">
                        ${member.job_title || 'Team Member'}
                    </p>
                    <div class="w-full h-0.5 bg-indigo-500/30 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-center"></div>
                </div>

                <div class="mt-2 opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300 ease-out">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <svg class="w-2.5 h-2.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        ${new Date(member.created_at).toLocaleDateString()}
                    </span>
                </div>
            </div>
            
            <!-- Background Glow Effect on Hover -->
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-indigo-50/30 to-indigo-100/30 dark:via-indigo-900/10 dark:to-indigo-800/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
        </div>
    `;
}
