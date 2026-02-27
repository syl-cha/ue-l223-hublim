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

// Carousel logic
document.addEventListener('DOMContentLoaded', () => {
        const swiper = new Swiper(".mySwiper", {
        effect: "coverflow",
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: 3,
        initialSlide: 1,
        loop: true,
        loopedSlides: 3,      // ✅ Exactement 3 copies virtuelles
        watchSlidesProgress: true,
        breakpoints: {
            0: {
                slidesPerView: 1,
            },
            768: {
                slidesPerView: "auto",
            }
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
            dynamicBullets: true,
        },
        coverflowEffect: {
            rotate: 0,
            stretch: 0,
            depth: 100,
            modifier: 3,
            slideShadows: true,
        },
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        }
    });


    window.addEventListener('scroll', () => {
        const header = document.querySelector('header');
        
        if (window.scrollY > 0) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
      


});