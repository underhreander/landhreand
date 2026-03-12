document.addEventListener('DOMContentLoaded', function() {
    // Header scroll effect
    const header = document.querySelector('.header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Download steps functionality
    const generateBtn = document.getElementById('generateCodeBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            // Generate random trial code (16 chars)
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let code = '';
            for (let i = 0; i < 16; i++) {
                if (i > 0 && i % 4 === 0) code += '-';
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            document.getElementById('trialCode').textContent = code;
            
            // Show step 2
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.querySelectorAll('.step-content').forEach(content => content.classList.remove('active'));
            
            document.getElementById('step2').classList.add('active');
            document.getElementById('step2-content').classList.add('active');
            
            // Log code generation (optional)
            logCodeGeneration(code);
        });
    }

    // Handle download button click with enhanced tracking
    const downloadBtn = document.getElementById('downloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            // Prevent default action temporarily
            e.preventDefault();
            
            // Get trial code from current session
            const trialCodeElement = document.getElementById('trialCode');
            const trialCode = trialCodeElement ? trialCodeElement.textContent.trim() : '';
            
            if (!trialCode) {
                alert('Please generate a trial code first!');
                return false;
            }
            
            // Log the download and then proceed with actual download
            logDownload(trialCode, () => {
                // After successful logging, proceed with download
                const downloadUrl = downloadBtn.getAttribute('href');
                
                // Create temporary link and trigger download
                const tempLink = document.createElement('a');
                tempLink.href = downloadUrl;
                tempLink.download = downloadBtn.hasAttribute('download') ? downloadBtn.getAttribute('download') : '';
                document.body.appendChild(tempLink);
                tempLink.click();
                document.body.removeChild(tempLink);
                
                // Show step 3
                setTimeout(() => {
                    document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
                    document.querySelectorAll('.step-content').forEach(content => content.classList.remove('active'));
                    
                    document.getElementById('step3').classList.add('active');
                    document.getElementById('step3-content').classList.add('active');
                    
                    // Scroll to step 3
                    document.getElementById('step3-content').scrollIntoView({ behavior: 'smooth' });
                }, 1000);
            });
        });
    }

    // Function to log code generation (when user clicks "Generate Code")
    function logCodeGeneration(code) {
        const payload = {
            action: 'code_generated',
            code: code,
            page: window.location.pathname,
            timestamp: new Date().toISOString()
        };

        fetch('admin/api/log_download.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Code generation logged successfully');
            } else {
                console.error('Failed to log code generation:', data.error);
            }
        })
        .catch(error => {
            console.error('Error logging code generation:', error);
        });
    }

    // Function to log actual download (when user clicks "Download")
    function logDownload(trialCode, callback) {
        const payload = {
            action: 'download_clicked',
            trial_code: trialCode,
            page: window.location.pathname,
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent
        };

        fetch('admin/api/log_download.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Download logged successfully:', data.data);
                if (callback) callback();
            } else {
                console.error('Failed to log download:', data.error);
                // Even if logging fails, still allow download
                if (callback) callback();
            }
        })
        .catch(error => {
            console.error('Error logging download:', error);
            // Even if logging fails, still allow download
            if (callback) callback();
        });
    }
});