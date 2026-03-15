import hljs from 'highlight.js';

// theme
import './css/hightlight_themes/stackoverflow-light.css';

document.addEventListener('turbo:load', function () {
    document
        .querySelectorAll('pre code:not(.language-math)') // ```math est interprété par KaTeX
        .forEach((block) => {
            delete block.dataset.highlighted;
            hljs.highlightElement(block);
        });
});
