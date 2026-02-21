/**
 * Stock Sathi Landing Page - JavaScript
 * Handles smooth scrolling, animations, and interactive elements
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===== Navbar Scroll Effect =====
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function () {
        if (window.scrollY > 50) {
            navbar.classList.add('shadow');
        } else {
            navbar.classList.remove('shadow');
        }
    });

    // ===== Smooth Scroll for Navigation Links =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));

            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    navbarCollapse.classList.remove('show');
                }
            }
        });
    });

    // ===== Active Navigation Link on Scroll =====
    const sections = document.querySelectorAll('section[id]');

    window.addEventListener('scroll', function () {
        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;

            if (window.pageYOffset >= sectionTop - 100) {
                current = section.getAttribute('id');
            }
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    // ===== Animate Elements on Scroll =====
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards and sections
    document.querySelectorAll('.feature-card, .pricing-card, .testimonial-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // ===== Counter Animation for Stats =====
    const counters = document.querySelectorAll('.stat-item h3');
    let counterAnimated = false;

    window.addEventListener('scroll', function () {
        const heroSection = document.querySelector('.hero-section');
        if (!heroSection) return;

        const heroBottom = heroSection.offsetTop + heroSection.offsetHeight;

        if (window.pageYOffset < heroBottom && !counterAnimated) {
            counterAnimated = true;

            counters.forEach(counter => {
                const target = counter.textContent;
                const isNumber = /^\d+/.test(target);

                if (isNumber) {
                    const value = parseInt(target.replace(/[^\d]/g, ''));
                    const suffix = target.replace(/[\d,]/g, '');
                    let current = 0;
                    const increment = value / 50;

                    const updateCounter = () => {
                        current += increment;
                        if (current < value) {
                            counter.textContent = Math.floor(current).toLocaleString() + suffix;
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target;
                        }
                    };

                    updateCounter();
                }
            });
        }
    });

    // ===== Pricing Card Highlight =====
    const pricingCards = document.querySelectorAll('.pricing-card');

    pricingCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            pricingCards.forEach(c => c.style.opacity = '0.7');
            this.style.opacity = '1';
        });

        card.addEventListener('mouseleave', function () {
            pricingCards.forEach(c => c.style.opacity = '1');
        });
    });

    // ===== Form Validation (if contact form exists) =====
    const contactForm = document.querySelector('#contactForm');

    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Add your form submission logic here
            alert('Thank you for your interest! We will contact you soon.');
            contactForm.reset();
        });
    }

    // ===== Lazy Loading for Images =====
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // ===== Mobile Menu Close on Outside Click =====
    document.addEventListener('click', function (e) {
        const navbar = document.querySelector('.navbar');
        const navbarCollapse = document.querySelector('.navbar-collapse');

        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            if (!navbar.contains(e.target)) {
                navbarCollapse.classList.remove('show');
            }
        }
    });

    console.log('Stock Sathi Landing Page Loaded Successfully! 🚀');
});
