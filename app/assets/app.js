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

    // Prévisualisation des photos à uploader
    const fileInput = document.querySelector('input[type="file"][multiple]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const existing = document.getElementById('preview-grid');
            if (existing) existing.remove();

            const files = Array.from(this.files);
            if (!files.length) return;

            const grid = document.createElement('div');
            grid.id = 'preview-grid';
            grid.className = 'row g-2 mt-2';

            // DataTransfer pour modifier la liste de fichiers
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));

            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-4';
                    col.dataset.index = index;
                    col.innerHTML = `
                        <div class="position-relative">
                            <img src="${e.target.result}"
                                class="w-100 rounded-2"
                                style="height:100px; object-fit:cover; opacity:0.85; border: 2px dashed var(--color-secondary);">
                            <button type="button"
                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle btn-remove-preview"
                                    data-index="${index}"
                                    style="width:24px; height:24px; padding:0; line-height:1; font-size:0.7rem;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    `;
                    grid.appendChild(col);

                    // Croix pour retirer de la prévisualisation
                    col.querySelector('.btn-remove-preview').addEventListener('click', function() {
                        const idx = parseInt(this.dataset.index);
                        dt.items.remove(idx);
                        fileInput.files = dt.files;

                        // Reconstruire les data-index
                        col.remove();
                        grid.querySelectorAll('.col-4').forEach((c, i) => {
                            c.dataset.index = i;
                            const btn = c.querySelector('.btn-remove-preview');
                            if (btn) btn.dataset.index = i;
                        });

                        if (dt.items.length === 0) grid.remove();
                    });
                };
                reader.readAsDataURL(file);
            });

            this.closest('.mt-2').insertAdjacentElement('afterend', grid);
        });
    }

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
        const newEl = el.cloneNode(true);
        el.parentNode.replaceChild(newEl, el);
        newEl.addEventListener('click', function() {
            openLightbox(parseInt(this.dataset.index));
        });
    });

    // Boutons lightbox — clone pour supprimer les anciens listeners
    ['lb-close', 'lb-prev', 'lb-next', 'lightbox'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const newEl = el.cloneNode(true);
        el.parentNode.replaceChild(newEl, el);
    });

    const lbClose = document.getElementById('lb-close');
    const lbPrev  = document.getElementById('lb-prev');
    const lbNext  = document.getElementById('lb-next');
    const lb      = document.getElementById('lightbox');

    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    if (lbPrev)  lbPrev.addEventListener('click', (e) => { e.stopPropagation(); changePhoto(-1); });
    if (lbNext)  lbNext.addEventListener('click', (e) => { e.stopPropagation(); changePhoto(1); });
    if (lb)      lb.addEventListener('click', closeLightbox);

    const lbContent = document.querySelector('.lb-content');
    if (lbContent) lbContent.addEventListener('click', (e) => e.stopPropagation());

    document.addEventListener('keydown', function(e) {
        const lb = document.getElementById('lightbox');
        if (!lb || !lb.classList.contains('active')) return;
        if (e.key === 'ArrowLeft')  changePhoto(-1);
        if (e.key === 'ArrowRight') changePhoto(1);
        if (e.key === 'Escape')     closeLightbox();
    });
}

// Gestion suppression photos — une seule fois
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-mark-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.imageId;
            const item = document.getElementById('photo-' + id);
            const checkbox = document.getElementById('delete-' + id);

            if (checkbox.checked) {
                checkbox.checked = false;
                item.style.display = '';
                item.style.opacity = '1';
                this.classList.remove('btn-secondary');
                this.classList.add('btn-danger');
            } else {
                checkbox.checked = true;
                item.style.transition = 'opacity 0.3s';
                item.style.opacity = '0';
                setTimeout(() => { item.style.display = 'none'; }, 300);
                this.classList.remove('btn-danger');
                this.classList.add('btn-secondary');
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', initPage);
document.addEventListener('turbo:load', initPage);