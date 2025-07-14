/**
 * ADLOR Camera Utilities
 * Centralized camera access and error handling functions
 */

// Check if camera is available
async function checkCameraAvailability() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return {
                available: false,
                error: 'UNSUPPORTED',
                message: 'Camera access is not supported in this browser'
            };
        }

        // Check if any cameras are available
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter(device => device.kind === 'videoinput');
        
        if (cameras.length === 0) {
            return {
                available: false,
                error: 'NO_CAMERA',
                message: 'No cameras found on this device'
            };
        }

        return {
            available: true,
            cameras: cameras.length,
            message: `${cameras.length} camera(s) available`
        };
    } catch (error) {
        return {
            available: false,
            error: error.name,
            message: error.message
        };
    }
}

// Request camera permission
async function requestCameraPermission() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user' } 
        });
        
        // Stop the stream immediately - we just wanted to check permission
        stream.getTracks().forEach(track => track.stop());
        
        return {
            granted: true,
            message: 'Camera permission granted'
        };
    } catch (error) {
        return {
            granted: false,
            error: error.name,
            message: error.message
        };
    }
}

// Generate detailed error message with troubleshooting
function generateCameraErrorMessage(error, context = 'general') {
    let errorMessage = '';
    let troubleshootingTips = '';
    
    switch(error.name) {
        case 'NotAllowedError':
            errorMessage = '❌ Camera access denied by user';
            troubleshootingTips = `
                <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                    <strong>🔧 How to fix:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Click the camera icon in your browser's address bar</li>
                        <li>Select "Allow" for camera access</li>
                        <li>Refresh the page and try again</li>
                        ${context === 'face_registration' ? '<li>Face registration requires camera access for security</li>' : '<li>Or use alternative scanning methods below</li>'}
                    </ul>
                </div>
            `;
            break;
        case 'NotFoundError':
            errorMessage = '❌ No camera found on this device';
            troubleshootingTips = `
                <div style="margin-top: 1rem; padding: 1rem; background: #fee2e2; border-radius: 0.5rem; border-left: 4px solid #ef4444;">
                    <strong>💡 Alternative options:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        ${context === 'face_registration' ? 
                            '<li>Try on a device with a camera</li><li>Use a laptop or smartphone with a front camera</li>' :
                            '<li>Use QR code scanning instead</li><li>Use manual Student ID entry</li><li>Try on a device with a camera</li>'
                        }
                    </ul>
                </div>
            `;
            break;
        case 'NotReadableError':
            errorMessage = '❌ Camera is being used by another application';
            troubleshootingTips = `
                <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                    <strong>🔧 How to fix:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Close other apps using the camera (Zoom, Skype, Teams, etc.)</li>
                        <li>Restart your browser</li>
                        <li>Try again in a few moments</li>
                        <li>Make sure no other browser tabs are using the camera</li>
                    </ul>
                </div>
            `;
            break;
        case 'OverconstrainedError':
            errorMessage = '❌ Camera doesn\'t meet requirements';
            troubleshootingTips = `
                <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                    <strong>🔧 How to fix:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Try using a different camera (if available)</li>
                        <li>Update your browser to the latest version</li>
                        <li>Try using a different browser (Chrome, Firefox, Safari)</li>
                    </ul>
                </div>
            `;
            break;
        case 'UNSUPPORTED':
            errorMessage = '❌ Camera access not supported';
            troubleshootingTips = `
                <div style="margin-top: 1rem; padding: 1rem; background: #fee2e2; border-radius: 0.5rem; border-left: 4px solid #ef4444;">
                    <strong>💡 What you can do:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Update your browser to the latest version</li>
                        <li>Try using a modern browser (Chrome, Firefox, Safari, Edge)</li>
                        <li>Use alternative scanning methods if available</li>
                    </ul>
                </div>
            `;
            break;
        default:
            errorMessage = '❌ Camera access failed';
            troubleshootingTips = `
                <div style="margin-top: 1rem; padding: 1rem; background: #e0f2fe; border-radius: 0.5rem; border-left: 4px solid #0284c7;">
                    <strong>💡 Troubleshooting steps:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Refresh the page and try again</li>
                        <li>Check if your browser supports camera access</li>
                        <li>Try using a different browser</li>
                        <li>Contact support if problem persists</li>
                    </ul>
                </div>
            `;
    }
    
    return errorMessage + troubleshootingTips;
}

// Display camera status in a container
function displayCameraStatus(containerId, status) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    if (status.available) {
        container.innerHTML = `
            <div style="background: #d1fae5; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #10b981; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">📷</span>
                    <div>
                        <strong style="color: #065f46;">Camera Ready</strong>
                        <div style="color: #065f46; font-size: 0.875rem;">${status.message}</div>
                    </div>
                </div>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div style="background: #fee2e2; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #ef4444; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">❌</span>
                    <div>
                        <strong style="color: #991b1b;">Camera Unavailable</strong>
                        <div style="color: #991b1b; font-size: 0.875rem;">${status.message}</div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialize camera check on page load
document.addEventListener('DOMContentLoaded', async function() {
    // Check if there's a camera status container on the page
    const statusContainer = document.getElementById('camera-status');
    if (statusContainer) {
        const status = await checkCameraAvailability();
        displayCameraStatus('camera-status', status);
    }
});

// Export functions for use in other scripts
window.CameraUtils = {
    checkCameraAvailability,
    requestCameraPermission,
    generateCameraErrorMessage,
    displayCameraStatus
};
