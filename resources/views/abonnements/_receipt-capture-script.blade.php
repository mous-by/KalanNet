{{-- Gestion de la caméra et de l'aperçu pour les champs de preuve photo
     (`.js-receipt-wrapper`) — partagé entre abonnements/index.blade.php et
     abonnements/blocked.blade.php pour éviter de dupliquer cette logique. --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-receipt-wrapper').forEach(wrapper => {
        const fileInput = wrapper.querySelector('.js-receipt-input');
        const btnCamera = wrapper.querySelector('.js-btn-camera');
        const cameraContainer = wrapper.querySelector('.js-camera-container');
        const video = wrapper.querySelector('.js-camera-video');
        const btnCapture = wrapper.querySelector('.js-btn-capture');
        const btnCloseCamera = wrapper.querySelector('.js-btn-close-camera');

        const previewContainer = wrapper.querySelector('.js-receipt-preview');
        const previewImg = wrapper.querySelector('.js-preview-img');
        const btnRemoveReceipt = wrapper.querySelector('.js-btn-remove-receipt');

        let stream = null;

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.classList.add('d-none');
            }
        });

        btnCamera.addEventListener('click', async function() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                cameraContainer.classList.remove('d-none');
                btnCamera.disabled = true;
                fileInput.disabled = true;
            } catch (err) {
                alert('Impossible d\'accéder à la caméra : ' + err.message);
            }
        });

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            cameraContainer.classList.add('d-none');
            btnCamera.disabled = false;
            fileInput.disabled = false;
        }

        btnCloseCamera.addEventListener('click', stopCamera);

        btnCapture.addEventListener('click', function() {
            if (!stream) return;

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(function(blob) {
                const file = new File([blob], "capture_camera.jpg", { type: "image/jpeg" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                fileInput.dispatchEvent(new Event('change'));
                stopCamera();
            }, 'image/jpeg', 0.8);
        });

        btnRemoveReceipt.addEventListener('click', function() {
            fileInput.value = '';
            previewContainer.classList.add('d-none');
            previewImg.src = '';
        });
    });
});
</script>
