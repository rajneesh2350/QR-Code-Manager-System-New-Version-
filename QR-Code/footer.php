</div> </div> <div id="qrModal" class="modal no-print">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Generate New QR Code</h2>

            <form id="qrForm" onsubmit="submitQRForm(event)">
                <input type="hidden" id="qrId" name="id">

                <div class="form-group">
                    <label for="qrname">QR Name:</label>
                    <input type="text" id="qrname" name="qrname" required placeholder="e.g., ATTENDANCE, PRODUCT123">
                </div>

                <div class="form-group">
                    <label for="finalurl">Destination URL:</label>
                    <input type="url" id="finalurl" name="finalurl" required placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label for="logo_selection" style="font-weight: 600;"><i class="fas fa-image" style="color: var(--primary);"></i> Select Logo:</label>
                    <select id="logo_selection" name="logo_selection" onchange="updateLogoPreview()" style="width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; background: white; margin-top: 5px;">
                        <option value="none">No Logo</option>
                        <option value="igipess" selected>IGIPESS Logo</option>
                        <option value="youtube">YouTube</option>
                        <option value="facebook">Facebook</option>
                        <option value="twitter">Twitter (X)</option>
                        <option value="instagram">Instagram</option>
                    </select>
                </div>

                <div class="form-group" id="logo_preview_container" style="text-align: center; margin-top: 15px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    <img id="logo_preview_img" src="https://igipess.du.ac.in/QR-Code/igipesslogo1.png" alt="Logo preview" style="max-width: 80px; max-height: 80px; border-radius: 5px; background: white; padding: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <p id="logo_help_text" style="font-size: 12px; color: #64748b; margin-top: 8px; margin-bottom: 0;">
                        <i class="fas fa-info-circle"></i> This logo will be centered inside the QR code
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 16px; margin-top: 15px;">Generate QR Code</button>
            </form>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Close sidebar if clicking on main content on mobile
        document.querySelector('.content-area').addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });

        // Live Logo Preview Logic
        const logoUrls = {
            'none': '',
            'igipess':  'https://igipess.du.ac.in/QR-Code/igipesslogo1.png',
            'youtube':  'https://igipess.du.ac.in/QR-Code/youtube.png',
            'facebook': 'https://igipess.du.ac.in/QR-Code/facebook.png',
            'twitter':  'https://igipess.du.ac.in/QR-Code/tweeter.png',
            'instagram':'https://igipess.du.ac.in/QR-Code/instagram.png'
        };

        function updateLogoPreview() {
            const selection = document.getElementById('logo_selection').value;
            const previewContainer = document.getElementById('logo_preview_container');
            const previewImg = document.getElementById('logo_preview_img');

            if (selection === 'none') {
                previewContainer.style.display = 'none';
            } else {
                previewImg.src = logoUrls[selection];
                previewContainer.style.display = 'block';
            }
        }

        // Modal Logic
        const modal = document.getElementById("qrModal");

        function openAddModal() {
            // Set text back to 'Generate' mode
            document.getElementById("modalTitle").innerText = "Generate New QR Code";
            const submitBtn = document.querySelector('#qrForm button[type="submit"]');
            if (submitBtn) submitBtn.innerText = "Generate QR Code";

            // Clear the form and the hidden ID
            document.getElementById("qrForm").reset();
            document.getElementById("qrId").value = "";

            // Make logo selector visible again for Add mode
            const logoContainer = document.getElementById("logo_selection");
            if (logoContainer && logoContainer.parentElement) {
                logoContainer.parentElement.style.display = 'block';
            }

            updateLogoPreview(); // Reset preview to default
            modal.style.display = "flex";
            if (window.innerWidth <= 992) toggleSidebar();
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // =========================================================================
        // BULLETPROOF GENERATE & EDIT QR LOGIC
        // =========================================================================
        function submitQRForm(event) {
            event.preventDefault(); // Stop normal form submission

            // 1. Capture the form data FIRST
            const formData = new FormData(document.getElementById('qrForm'));

            // 2. Check if it's an EDIT or ADD operation based on the hidden ID
            const isEdit = document.getElementById('qrId').value !== "";
            const targetUrl = isEdit ? 'edit-qr.php' : 'add-qr.php';

            // 3. Force the submit button to lose focus to prevent the aria-hidden error
            if(document.activeElement) {
                document.activeElement.blur();
            }

            // 4. Close the custom modal to clean up the screen
            closeModal();

            // 5. Trigger the SweetAlert loading popup safely
            Swal.fire({
                title: isEdit ? 'Updating...' : 'Generating...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 6. Send the data to the correct server file
            fetch(targetUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // Read as raw text first
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if(data.success || data.success === 'true') {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message || (isEdit ? 'QR code updated successfully!' : 'QR code generated successfully!'),
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Action failed.', 'error');
                    }
                } catch (err) {
                    console.error("Server Response:", text);
                    Swal.fire('Error', 'Unexpected server response. Check console.', 'error');
                }
            })
            .catch(error => {
                console.error('Network Error:', error);
                Swal.fire('Error', 'A network error occurred.', 'error');
            });
        }

        // =========================================================================
        // GLOBAL MEAL COUPON DATABASE RESET (Works from any page)
        // =========================================================================
        function resetCouponDatabase() {
            Swal.fire({
                title: 'Are you absolutely sure?',
                text: "This will wipe ALL previously scanned coupons and logs! You cannot undo this.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reset everything!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Resetting...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch('reset-coupons.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Reset Complete!', data.message, 'success');
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'A network error occurred.', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>