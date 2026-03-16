import './stimulus_bootstrap.js';
import './vendor/bootstrap/dist/css/bootstrap.min.css'
import './styles/app.css';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Carousel & Coloration
import './katex-init.js';
import './hightlight-init.js';

console.log('JS chargé avec succès !');

function initPage() {
    // 1. Carousel logic (Swiper)
    const swiperEl = document.querySelector('.mySwiper');
    if (swiperEl && typeof Swiper !== 'undefined') {
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

        swiper.on('click', () => {
            if (swiper.clickedIndex !== undefined && swiper.clickedIndex !== swiper.activeIndex) {
                swiper.slideTo(swiper.clickedIndex);
            }
        });
    }

    // 2. Header scroll logic
    const header = document.querySelector('header');
    const handleScroll = () => {
        if (window.scrollY > 0 || !document.body.classList.contains('home-page')) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', handleScroll);
    handleScroll();

    // 3. Prévisualisation des nouvelles photos (SANS DOUBLONS)
    const fileInput = document.getElementById('card_imageFiles');
    const grid = document.getElementById('preview-grid');

    if (fileInput && grid) {
        fileInput.addEventListener('change', function() {
            // On vide la grille à chaque changement pour éviter les doublons
            grid.innerHTML = ''; 

            const files = Array.from(this.files);
            if (!files.length) return;

            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-4 mb-3';
                    col.innerHTML = `
                        <div class="position-relative">
                            <img src="${e.target.result}" class="w-100 rounded-2" style="height:100px; object-fit:cover; border: 2px dashed #ffc107;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle btn-remove-preview" 
                                    data-index="${index}" style="width:24px; height:24px; padding:0; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    `;
                    grid.appendChild(col);

                    // Gestion du bouton supprimer de la prévisualisation
                    col.querySelector('.btn-remove-preview').onclick = function(event) {
                        event.preventDefault();
                        const idxToRemove = parseInt(this.dataset.index);
                        const dt = new DataTransfer();
                        
                        // Filtrer les fichiers de l'input
                        for (let i = 0; i < fileInput.files.length; i++) {
                            if (i !== idxToRemove) {
                                dt.items.add(fileInput.files[i]);
                            }
                        }
                        
                        fileInput.files = dt.files;
                        // On déclenche manuellement 'change' pour redessiner la grille proprement
                        fileInput.dispatchEvent(new Event('change'));
                    };
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // 4. Gestion des photos existantes (btn-mark-delete)
    document.querySelectorAll('.btn-mark-delete').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.imageId;
            const item = document.getElementById('photo-' + id);
            const checkbox = document.getElementById('delete-' + id);

            if (checkbox && item) {
                if (checkbox.checked) {
                    checkbox.checked = false;
                    item.style.opacity = '1';
                    item.style.filter = 'none';
                    this.classList.replace('btn-secondary', 'btn-danger');
                } else {
                    checkbox.checked = true;
                    item.style.opacity = '0.3';
                    item.style.filter = 'grayscale(1)';
                    this.classList.replace('btn-danger', 'btn-secondary');
                }
            }
        };
    });

    // 5. Initialisation Lightbox
    initLightbox();
}

/**
 * LOGIQUE LIGHTBOX
 */
let currentIndex = 0;
function initLightbox() {
    document.querySelectorAll('.fb-img, .fb-overlay').forEach(el => {
        const newEl = el.cloneNode(true);
        el.parentNode.replaceChild(newEl, el);
        newEl.addEventListener('click', function() {
            openLightbox(parseInt(this.dataset.index));
        });
    });

    // On clone les contrôles pour éviter les doublons d'événements avec Turbo
    ['lb-close', 'lb-prev', 'lb-next', 'lightbox'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const newEl = el.cloneNode(true);
            el.parentNode.replaceChild(newEl, el);
        }
    });

    document.getElementById('lb-close')?.addEventListener('click', closeLightbox);
    document.getElementById('lb-prev')?.addEventListener('click', (e) => { e.stopPropagation(); changePhoto(-1); });
    document.getElementById('lb-next')?.addEventListener('click', (e) => { e.stopPropagation(); changePhoto(1); });
    document.getElementById('lightbox')?.addEventListener('click', closeLightbox);
}

function openLightbox(index) {
    const images = window.galleryImages || [];
    if (!images.length) return;
    currentIndex = index;
    document.getElementById('lightbox')?.classList.add('active');
    document.body.style.overflow = 'hidden';
    updateLightbox();
}

function closeLightbox() {
    document.getElementById('lightbox')?.classList.remove('active');
    document.body.style.overflow = '';
}

function changePhoto(direction) {
    const images = window.galleryImages || [];
    currentIndex = (currentIndex + direction + images.length) % images.length;
    updateLightbox();
}

function updateLightbox() {
    const images = window.galleryImages || [];
    const imgData = images[currentIndex];
    const lbImg = document.getElementById('lb-img');
    const lbCounter = document.getElementById('lb-counter');
    if (lbImg && imgData) {
        lbImg.src = imgData.src;
        if (lbCounter) lbCounter.textContent = (currentIndex + 1) + ' / ' + images.length;
    }
}

// Turbo & Initialisation
document.addEventListener('turbo:load', initPage);