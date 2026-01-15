import { auth } from './services/auth.js';
import { eventBus } from './app.js';
import { toast } from './ui/toast.js';

function viewUserDetails(userId) {
    if (!userId) {
        showError('Invalid user ID');
        return;
    }

    // Show loading state
    const modalBody = document.querySelector('#userDetailsModal .modal-body');
    if (modalBody) {
        modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
    }

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();

    // Fetch user details
    fetch(`api/admin.php?action=get_user_details&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                modalBody.innerHTML = `
                    <div class="user-details">
                        <p><strong>Name:</strong> ${data.user.first_name} ${data.user.last_name}</p>
                        <p><strong>Email:</strong> ${data.user.email}</p>
                        <p><strong>Department:</strong> ${data.user.department || 'N/A'}</p>
                        <p><strong>Created:</strong> ${new Date(data.user.created_at).toLocaleDateString()}</p>
                        <p><strong>Tasks Completed:</strong> ${data.stats.completed_tasks || 0}</p>
                        <p><strong>Tasks Pending:</strong> ${data.stats.pending_tasks || 0}</p>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${data.message || 'Error loading user details'}</div>`;
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load user details</div>';
            console.error('Error:', error);
        });
}

function showError(message) {
    // You can implement this based on your UI needs
    alert(message);
}

function addNewUser(formData) {
    const userData = {
        email: formData.get('email'),
        password: formData.get('password'),
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        department: formData.get('department')
    };

    fetch('api/admin.php?action=add_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('User created successfully');
            // Optionally refresh user list or close modal
            if (typeof refreshUserList === 'function') {
                refreshUserList();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create user');
    });
}

async function logout() {
    try {
        await auth.logout();
        // The session:updated event will handle the redirect
    } catch (error) {
        toast.error('Logout failed. Please try again.');
        console.error('Logout error:', error);
    }
}

// Add event listener for logout buttons
document.querySelectorAll('[data-action="logout"]').forEach(button => {
    button.addEventListener('click', logout);
});
