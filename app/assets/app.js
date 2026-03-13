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

    // Lightbox
    initLightbox();
}

// Lightbox
let currentIndex = 0;

function openLightbox(index) {
    const images = window.galleryImages || [];
    if (!images.length) return;
    currentIndex = index;
    const lb = document.getElementById('lightbox');
    if (!lb) return;
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
    updateLightbox();
}

function closeLightbox() {
    const lb = document.getElementById('lightbox');
    if (!lb) return;
    lb.classList.remove('active');
    document.body.style.overflow = '';
}

function changePhoto(direction) {
    const images = window.galleryImages || [];
    currentIndex = (currentIndex + direction + images.length) % images.length;
    updateLightbox();
}

function updateLightbox() {
    const images = window.galleryImages || [];
    const img = images[currentIndex];
    if (!img) return;
    document.getElementById('lb-img').src = img.src;
    document.getElementById('lb-img').alt = img.alt;
    document.getElementById('lb-counter').textContent = (currentIndex + 1) + ' / ' + images.length;
}

function initLightbox() {
    // Clic sur les images et overlay
    document.querySelectorAll('.fb-img, .fb-overlay').forEach(el => {
        el.addEventListener('click', function() {
            openLightbox(parseInt(this.dataset.index));
        });
    });

    // Boutons lightbox
    const lbClose = document.getElementById('lb-close');
    const lbPrev  = document.getElementById('lb-prev');
    const lbNext  = document.getElementById('lb-next');
    const lb      = document.getElementById('lightbox');

    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    if (lbPrev)  lbPrev.addEventListener('click', (e) => { e.stopPropagation(); changePhoto(-1); });
    if (lbNext)  lbNext.addEventListener('click', (e) => { e.stopPropagation(); changePhoto(1); });
    if (lb)      lb.addEventListener('click', closeLightbox);

    // Empêcher la fermeture au clic sur le contenu
    const lbContent = document.querySelector('.lb-content');
    if (lbContent) lbContent.addEventListener('click', (e) => e.stopPropagation());

    // Clavier
    document.addEventListener('keydown', function(e) {
        const lb = document.getElementById('lightbox');
        if (!lb || !lb.classList.contains('active')) return;
        if (e.key === 'ArrowLeft')  changePhoto(-1);
        if (e.key === 'ArrowRight') changePhoto(1);
        if (e.key === 'Escape')     closeLightbox();
    });
}

// Premier chargement
document.addEventListener('DOMContentLoaded', initPage);
// Navigations Turbo (après déconnexion, etc.)
document.addEventListener('turbo:load', initPage);
