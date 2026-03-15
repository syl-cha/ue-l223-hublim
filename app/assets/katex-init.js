// assets/katex-init.js

document.addEventListener('turbo:load', function () {
    // Vérification que le script auto-render de KaTeX est bien chargé
    if (typeof renderMathInElement !== 'function') return;

    // 1. Détection classique (Inline) : KaTeX ignore les balises <code> pour protéger le vrai code
    renderMathInElement(document.body, {
        delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '$', right: '$', display: false },
        ],
        throwOnError: false,
    });

    // 2. Détection spécifique des blocs Markdown "math" (Display)
    document.querySelectorAll('code.language-math').forEach(function (block) {
        let mathContainer = document.createElement('div');

        // On demande à KaTeX de dessiner le texte brut du bloc
        katex.render(block.textContent, mathContainer, {
            displayMode: true,
            throwOnError: false,
        });

        // On remplace le gros bloc <pre><code> par notre belle équation
        if (block.parentElement && block.parentElement.tagName === 'PRE') {
            block.parentElement.replaceWith(mathContainer);
        }
    });
});
