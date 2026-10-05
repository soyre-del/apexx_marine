document.addEventListener('DOMContentLoaded', function () {
    let isScrolling;
    const navbar = document.getElementById('mainNav');
    if (!navbar) return;

    window.addEventListener('scroll', function () {
        window.clearTimeout(isScrolling);
        // Always show navbar if we are at the very top of the page
        if (window.scrollY === 0) {
            navbar.classList.remove('navbar-dissolved');
            return;
        }

        // Dissolve the navbar while scrolling
        navbar.classList.add('navbar-dissolved');

        // Clear the timeout throughout the scroll

        // Set a timeout to run after scrolling ends
        isScrolling = setTimeout(function() {
            // Reappear the navbar when scrolling stops
            navbar.classList.remove('navbar-dissolved');
        }, 250); // 250 milliseconds wait time after stop
    }, { passive: true });
});
