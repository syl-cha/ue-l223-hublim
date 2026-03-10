import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './vendor/bootstrap/dist/css/bootstrap.min.css'
import './styles/app.css';
// Carousel
import './stimulus_bootstrap.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! ');

function initPage() {
    // Carousel logic
    const swiperEl = document.querySelector('.mySwiper');
    if (swiperEl) {
        const swiper = new Swiper(".mySwiper", {
            effect: "coverflow",
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: "auto",
            initialSlide: 1,
            watchSlidesProgress: true,
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            coverflowEffect: {
                rotate: 0,
                stretch: 0,
                depth: 100,
                modifier: 3,
                slideShadows: true,
            },
            autoplay: {
                delay: 6000,
                disableOnInteraction: false,
            },
            loop: true,
        });

        // Clic sur une slide non-active pour y naviguer
        swiper.on('click', () => {
            if (swiper.clickedIndex !== undefined && swiper.clickedIndex !== swiper.activeIndex) {
                swiper.slideTo(swiper.clickedIndex);
            }
        });
    }

    // Header scroll
    window.addEventListener('scroll', () => {
        const header = document.querySelector('header');

        if (window.scrollY > 0) {
            header.classList.add('scrolled');
        } else {
            if (!document.body.classList.contains('home-page')) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    });

    // Au chargement de la page
    if (!document.body.classList.contains('home-page')) {
        document.querySelector('header').classList.add('scrolled');
    }
}

// Premier chargement
document.addEventListener('DOMContentLoaded', initPage);
// Navigations Turbo (après déconnexion, etc.)
document.addEventListener('turbo:load', initPage);
