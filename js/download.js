document.addEventListener('DOMContentLoaded', function() {
    const trialForm = document.getElementById('trialForm');
    const codeResult = document.getElementById('codeResult');
    const trialCode = document.getElementById('trialCode');
    const downloadBox = document.getElementById('downloadBox');

    if (trialForm) {
        trialForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Generate random trial code
            const code = generateTrialCode();
            trialCode.textContent = code;
            
            // Show code result
            codeResult.classList.remove('hidden');
            
            // After 2 seconds, show download button
            setTimeout(function() {
                downloadBox.classList.remove('hidden');
                
                // Scroll to download button
                downloadBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Log download in database
                logDownload(code);
            }, 2000);
        });
    }

    function generateTrialCode() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let code = '';
        
        for (let i = 0; i < 16; i++) {
            if (i > 0 && i % 4 === 0) code += '-';
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        return code;
    }

    function logDownload(code) {
        // In a real implementation, this would send data to the server
        fetch('admin/api/log_download.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                code: code,
                email: document.getElementById('userEmail').value
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Download logged:', data);
        })
        .catch(error => {
            console.error('Error logging download:', error);
        });
    }
});