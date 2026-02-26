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
        loop: true,
        pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
        slidesPerView: 3,
        coverflowEffect: {
            rotate: 0,
            stretch: 0,
            depth: 150,
            modifier: 2.5,
            slideShadows: true,
        },
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        }
    });
});