// Modal functions
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Generate New QR Code';
    document.getElementById('qrForm').reset();
    document.getElementById('qrId').value = '';
    document.getElementById('qrForm').action = 'add-qr.php';
    document.getElementById('qrModal').classList.add('show');
}

function openEditModal(id) {
    fetch(`edit-qr.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                document.getElementById('modalTitle').textContent = 'Edit QR Code';
                document.getElementById('qrId').value = data.id;
                document.getElementById('qrname').value = data.qrname;
                document.getElementById('finalurl').value = data.finalurl;
                document.getElementById('qrForm').action = 'edit-qr.php';
                document.getElementById('qrModal').classList.add('show');
            }
        });
}

function closeModal() {
    document.getElementById('qrModal').classList.remove('show');
}

// Form submission
document.getElementById('qrForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const action = this.action;

    fetch(action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message
            });
        }
    });
});

// Delete function
function deleteQR(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete-qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Deleted!',
                        data.message,
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Error!',
                        data.message,
                        'error'
                    );
                }
            });
        }
    });
}

// Download QR code
function downloadQR(imagePath) {
    const link = document.createElement('a');
    link.href = imagePath;
    link.download = imagePath.split('/').pop();
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = document.querySelectorAll('.qr-card');

    cards.forEach(card => {
        const name = card.dataset.name;
        if (name.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('qrModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Edit function
function editQR(id) {
    openEditModal(id);
}

// Refresh stats periodically
setInterval(() => {
    fetch('get-qr-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update stats if needed
            console.log('Stats updated');
        });
}, 30000); // Refresh every 30 seconds