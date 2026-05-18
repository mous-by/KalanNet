document.addEventListener("DOMContentLoaded", function () {
  const texts = [
    "Bienvenue sur Malian School Information Software (MSIS)...",
    "Objectif : Le projet MSIS est une initiative majeure de DigitAfrika.",
    "Conçue pour moderniser et optimiser la gestion.",
    "des informations au sein des établissements scolaires maliens.",
    "Les principaux bénéficiaires du projet MSIS sont :",
    "les établissements scolaires privés et publics du Mali.",
  ];

  const target = document.getElementById("typed-text");
  let textIndex = 0;
  let charIndex = 0;
  let typing = true;

  function typeEffect() {
    if (!target) return; // safety: do nothing if target is missing
    const currentText = texts[textIndex];

    if (typing) {
      if (charIndex < currentText.length) {
        target.textContent += currentText.charAt(charIndex);
        charIndex++;
        setTimeout(typeEffect, 60);
      } else {
        typing = false;
        setTimeout(typeEffect, 2000);
      }
    } else {
      if (charIndex > 0) {
        target.textContent = currentText.substring(0, charIndex - 1);
        charIndex--;
        setTimeout(typeEffect, 30);
      } else {
        typing = true;
        textIndex = (textIndex + 1) % texts.length;
        setTimeout(typeEffect, 400);
      }
    }
  }

  if (target) typeEffect(); // start only if target exists
});
 
 document.querySelectorAll('.school-form').forEach(form => {
      form.addEventListener('submit', function(e) {
          const button = this.querySelector('button');
          const originalText = button.innerHTML;
          button.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Connexion...';
          button.disabled = true;
          setTimeout(() => {
              button.innerHTML = originalText;
              button.disabled = false;
          }, 5000);
      });
  });
  document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('choixEcoleModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });