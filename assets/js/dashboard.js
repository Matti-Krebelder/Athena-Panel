// assets/js/dashboard-servers.js
document.addEventListener('DOMContentLoaded', () => {
    // Add click event listeners to server items
    const serverItems = document.querySelectorAll('.server-item');
    serverItems.forEach(item => {
        item.addEventListener('click', function(event) {
            // Only proceed if not clicking on the manage button
            if (!event.target.closest('.server-actions')) {
                const ip = this.dataset.ip;
                copyServerIP(ip);
                event.preventDefault();
                return false;
            }
        });
    });
});

function copyServerIP(ip) {
    navigator.clipboard.writeText(ip).then(() => {
        const toast = document.getElementById('toast-message');
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }).catch(err => {
        console.error('Could not copy IP: ', err);
    });
}