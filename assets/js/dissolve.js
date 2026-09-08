    let isScrolling;
    const navbar = document.getElementById('mainNav');

    window.addEventListener('scroll', function (event) {
        // Always show navbar if we are at the very top of the page
        if (window.scrollY === 0) {
            navbar.classList.remove('navbar-dissolved');
            return;
        }

        // Dissolve the navbar while scrolling
        navbar.classList.add('navbar-dissolved');

        // Clear the timeout throughout the scroll
        window.clearTimeout(isScrolling);

        // Set a timeout to run after scrolling ends
        isScrolling = setTimeout(function() {
            // Reappear the navbar when scrolling stops
            navbar.classList.remove('navbar-dissolved');
        }, 250); // 250 milliseconds wait time after stop
    }, false);