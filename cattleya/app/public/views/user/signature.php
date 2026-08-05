<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Signature | Cattleya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --brand: #673de6;
            --brand-hover: #5632c2;
            --brand-soft: rgba(103, 61, 230, 0.08);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(103, 61, 230, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(103, 61, 230, 0.05) 0px, transparent 50%);
        }

        .upload-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            width: 90%;
            max-width: 440px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 { font-weight: 800; font-size: 1.75rem; margin-bottom: 0.5rem; letter-spacing: -0.025em; }
        .subtitle { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; }

        /* Modern Dropzone */
        .dropzone-area {
            position: relative;
            border: 2px dashed #e2e8f0;
            border-radius: 24px;
            padding: 2.5rem 1.5rem;
            background: #fafafa;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            margin-bottom: 1.5rem;
        }

        .dropzone-area:hover {
            border-color: var(--brand);
            background: var(--brand-soft);
            transform: scale(1.01);
        }

        .dz-icon { font-size: 2.5rem; color: var(--brand); margin-bottom: 1rem; }
        .dz-text { display: block; font-weight: 700; color: var(--text-main); }
        .dz-subtext { font-size: 0.8rem; color: var(--text-muted); }

        /* Hidden File Input */
        #signatureFile {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }

        /* Preview Container */
        #previewWrapper {
            display: none;
            position: relative;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border-radius: 24px;
            background: #ffffff;
            border: 2px solid var(--brand);
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        #previewImg {
            max-width: 100%;
            max-height: 150px;
            display: block;
            margin: 0 auto;
        }

        .file-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--brand);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .remove-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ef4444;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
        }

        /* Action Button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            border: none;
            background: var(--brand);
            color: var(--white);
            font-weight: 700;
            border-radius: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -5px rgba(103, 61, 230, 0.4);
        }

        .btn-submit:hover {
            background: var(--brand-hover);
            transform: translateY(-2px);
        }

        .btn-submit:disabled { background: #cbd5e1; box-shadow: none; }
        
        .spinner-border-sm {
            width: 1rem; height: 1rem; border: 2px solid currentColor;
            border-right-color: transparent; border-radius: 50%;
            display: inline-block; animation: spin 0.75s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="upload-card">
    <div style="color: var(--brand); font-size: 2.5rem; margin-bottom: 1rem;">
        <i class="bi bi-vector-pen"></i>
    </div>
    <h2>Save Signature</h2>
    <p class="subtitle">Upload any image format (JPG, PNG, WebP, SVG, HEIC, etc.) of your signature.</p>

    <form id="signatureForm">
        <div class="dropzone-area" id="dropzone">
            <i class="bi bi-cloud-arrow-up dz-icon"></i>
            <span class="dz-text" id="fileNameDisplay">Choose a file</span>
            <span class="dz-subtext">Click or drag & drop</span>
            <input type="file" id="signatureFile" name="signature" accept="image/*" required>
        </div>

        <div id="previewWrapper">
            <button type="button" class="remove-btn" id="clearBtn"><i class="bi bi-x-lg"></i></button>
            <div class="file-badge" id="extBadge">IMG</div>
            <img src="" id="previewImg" alt="Signature Preview">
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            Confirm & Save
        </button>
    </form>
</div>

<script>
const fileInput = document.getElementById('signatureFile');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const previewWrapper = document.getElementById('previewWrapper');
const previewImg = document.getElementById('previewImg');
const dropzone = document.getElementById('dropzone');
const clearBtn = document.getElementById('clearBtn');
const extBadge = document.getElementById('extBadge');

fileInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        
        // Basic validation to ensure it IS an image
        if (!file.type.startsWith('image/')) {
            Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Please upload an image file.' });
            this.value = '';
            return;
        }

        // Display extension in badge
        const extension = file.name.split('.').pop();
        extBadge.textContent = extension;
        fileNameDisplay.textContent = file.name;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewWrapper.style.display = 'block';
            dropzone.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});

clearBtn.addEventListener('click', function() {
    fileInput.value = '';
    previewWrapper.style.display = 'none';
    dropzone.style.display = 'block';
    fileNameDisplay.textContent = 'Choose a file';
});

document.getElementById('signatureForm').addEventListener('submit', function(e){
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border-sm"></span> Processing...';

    const formData = new FormData();
    formData.append('signature', fileInput.files[0]);

    fetch('/cattleya/user/save-signature', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(response => {
        if(response.success){
            Swal.fire({ title: 'Success!', text: 'Signature updated.', icon: 'success', timer: 2000, showConfirmButton: false })
            .then(() => { window.location.href = '/cattleya/user/encoder/dashboard'; });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Confirm & Save';
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'System Error', text: 'Connection failed.' });
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Confirm & Save';
    });
});
</script>
</body>
</html>