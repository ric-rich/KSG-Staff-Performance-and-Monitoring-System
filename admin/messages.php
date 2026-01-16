<?php
require_once __DIR__ . '/../inc/auth.php';

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .header { background: linear-gradient(135deg, #4F46E5, #818CF8); }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .message-row.unread { background-color: #eff6ff; font-weight: 600; }
        .message-row:hover { background-color: #f3f4f6; }
        .action-btn { transition: background-color 0.2s; }
        .modal-bg { background-color: rgba(0,0,0,0.5); }
    </style>
</head>
<body>

    <div class="header text-white p-8 rounded-b-2xl shadow-lg">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Contact Messages</h1>
                    <p class="text-indigo-200 mt-1">Manage messages from the website contact form.</p>
                </div>
                <a href="../INDEX.HTML" onclick="showAdminDashboard()" class="bg-white/20 hover:bg-white/30 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card bg-white p-6 rounded-xl shadow">
                <h3 class="text-gray-500 font-medium">Total Messages</h3>
                <p id="totalMessages" class="text-3xl font-bold text-gray-800 mt-2">0</p>
            </div>
            <div class="stat-card bg-white p-6 rounded-xl shadow">
                <h3 class="text-gray-500 font-medium">Unread Messages</h3>
                <p id="unreadMessages" class="text-3xl font-bold text-indigo-600 mt-2">0</p>
            </div>
            <div class="stat-card bg-white p-6 rounded-xl shadow">
                <h3 class="text-gray-500 font-medium">Read Messages</h3>
                <p id="readMessages" class="text-3xl font-bold text-gray-800 mt-2">0</p>
            </div>
        </div>

        <!-- Message Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Inbox</h2>
                    <div class="flex space-x-2">
                        <button onclick="filterMessages('all')" class="filter-btn bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium">All</button>
                        <button onclick="filterMessages('unread')" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Unread</button>
                        <button onclick="filterMessages('read')" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Read</button>
                    </div>
                </div>
            </div>
            <div id="messagesContainer" class="p-4">
                <div id="loading" class="text-center p-8">
                    <p class="text-gray-500">Loading messages...</p>
                </div>
                <div id="messagesList" class="divide-y divide-gray-200"></div>
            </div>
        </div>
    </main>

    <!-- Message Detail Modal -->
    <div id="messageModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-bg flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="p-6 border-b">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="modalSubject" class="text-xl font-bold text-gray-800"></h3>
                        <p class="text-sm text-gray-500 mt-1">From: <span id="modalFrom"></span></p>
                    </div>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
            </div>
            <div id="modalBody" class="p-6 text-gray-700 leading-relaxed max-h-96 overflow-y-auto"></div>
            <div class="p-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
                <button id="modalReplyBtn" class="action-btn bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">Reply</button>
                <button id="modalDeleteBtn" class="action-btn bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg">Delete</button>
                <button onclick="closeModal()" class="action-btn bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg">Close</button>
            </div>
        </div>
    </div>

<script>
let currentFilter = 'all';
let allMessages = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchMessages();
});

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
    };
    if (data) options.body = JSON.stringify(data);

    try {
        const response = await fetch(`../api/admin.php?${endpoint}`, options);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('API Call Error:', error);
        alert('An error occurred. Please check the console.');
        return { status: 'error', message: error.message };
    }
}

async function fetchMessages() {
    document.getElementById('loading').style.display = 'block';
    const data = await apiCall('action=get_messages');
    document.getElementById('loading').style.display = 'none';

    if (data.status === 'success') {
        allMessages = data.messages;
        updateStats();
        renderMessages();
    }
}

function updateStats() {
    const total = allMessages.length;
    const unread = allMessages.filter(m => m.is_read == 0).length;
    const read = total - unread;

    document.getElementById('totalMessages').textContent = total;
    document.getElementById('unreadMessages').textContent = unread;
    document.getElementById('readMessages').textContent = read;
}

function renderMessages() {
    const list = document.getElementById('messagesList');
    const filteredMessages = allMessages.filter(m => {
        if (currentFilter === 'all') return true;
        if (currentFilter === 'read') return m.is_read == 1;
        if (currentFilter === 'unread') return m.is_read == 0;
    });

    if (filteredMessages.length === 0) {
        list.innerHTML = `<div class="text-center p-8 text-gray-500">No messages in this category.</div>`;
        return;
    }

    list.innerHTML = filteredMessages.map(msg => `
        <div class="message-row ${msg.is_read == 0 ? 'unread' : ''} p-4 cursor-pointer transition" onclick="showMessageDetail(${msg.id})">
            <div class="grid grid-cols-12 gap-4 items-center">
                <div class="col-span-3 truncate">
                    <p class="text-gray-800">${msg.name}</p>
                    <p class="text-sm text-gray-500">${msg.email}</p>
                </div>
                <div class="col-span-6 truncate">
                    <p class="text-gray-800">${msg.subject}</p>
                    <p class="text-sm text-gray-500">${msg.message.substring(0, 80)}...</p>
                </div>
                <div class="col-span-3 text-right text-sm text-gray-500">
                    ${new Date(msg.created_at).toLocaleString()}
                </div>
            </div>
        </div>
    `).join('');
}

function filterMessages(filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
    });
    event.target.classList.add('bg-indigo-600', 'text-white');
    event.target.classList.remove('bg-gray-200', 'text-gray-700');
    renderMessages();
}

async function showMessageDetail(id) {
    const message = allMessages.find(m => m.id == id);
    if (!message) return;

    document.getElementById('modalSubject').textContent = message.subject;
    document.getElementById('modalFrom').textContent = `${message.name} <${message.email}>`;
    document.getElementById('modalBody').innerHTML = message.message.replace(/\n/g, '<br>');
    
    document.getElementById('modalReplyBtn').onclick = () => {
        window.location.href = `mailto:${message.email}?subject=Re: ${message.subject}`;
    };
    document.getElementById('modalDeleteBtn').onclick = () => deleteMessage(id);

    document.getElementById('messageModal').classList.remove('hidden');

    if (message.is_read == 0) {
        await markAsRead(id);
    }
}

function closeModal() {
    document.getElementById('messageModal').classList.add('hidden');
}

async function markAsRead(id) {
    const message = allMessages.find(m => m.id == id);
    if (message) {
        message.is_read = 1;
        updateStats();
        renderMessages();
        await apiCall(`action=mark_message_read&id=${id}`, 'POST');
    }
}

async function deleteMessage(id) {
    if (!confirm('Are you sure you want to delete this message? This cannot be undone.')) {
        return;
    }

    const result = await apiCall(`action=delete_message&id=${id}`, 'DELETE');
    if (result.status === 'success') {
        allMessages = allMessages.filter(m => m.id != id);
        closeModal();
        updateStats();
        renderMessages();
        alert('Message deleted successfully.');
    } else {
        alert('Failed to delete message.');
    }
}
</script>
</body>
</html>