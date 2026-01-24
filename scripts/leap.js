function createStars() {
    const starsContainer = document.getElementById('stars');
    if (!starsContainer) return;
    const starCount = 100;
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        const size = Math.random() * 3;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        const duration = 2 + Math.random() * 3;
        const delay = Math.random() * 5;
        star.style.animation = `fly ${duration}s linear ${delay}s infinite`;
        starsContainer.appendChild(star);
    }
}

function startCountdown() {
    let seconds = 5;
    const countdownEl = document.getElementById('countdown');
    const skipBtn = document.getElementById('skipBtn');
    if (!countdownEl || !skipBtn) return;
    
    const timer = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = skipBtn.href;
        }
    }, 1000);
}

document.addEventListener('DOMContentLoaded', function() {
    createStars();
    startCountdown();
});