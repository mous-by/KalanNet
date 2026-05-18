document.addEventListener("DOMContentLoaded", () => {
  const rows = document.querySelectorAll("tbody tr");
  const form = document.querySelector("form");
  const submitButton = document.querySelector('button[type="submit"]');

  function checkPayments() {
    let allValid = true;

    rows.forEach((row) => {
      const totalInput = row.querySelector('input[name="montant_total[]"]');
      const payeInput = row.querySelector('input[name="montant_paye[]"]');
      const resteInput = row.querySelector('input[name="reste_a_payer[]"]');
      const isFirstPayment =
        parseFloat(resteInput.dataset.initial) === parseFloat(totalInput.value);

      const total = parseFloat(totalInput.value) || 0;
      const paye = parseFloat(payeInput.value) || 0;
      const reste = parseFloat(resteInput.value) || 0;

      // Pour le premier paiement: vérifier par rapport au montant total
      // Pour les paiements suivants: vérifier par rapport au reste
      const maxAllowed = isFirstPayment ? total : reste;

      const isNegative = payeInput.value.startsWith("-");
      const isValidPayment = !isNegative && paye >= 0 && paye <= maxAllowed;

      if (!isValidPayment) {
        allValid = false;
        payeInput.classList.add("invalid");
      } else {
        payeInput.classList.remove("invalid");
      }
    });

    submitButton.style.display = allValid ? "block" : "none";
  }

  rows.forEach((row) => {
    const totalInput = row.querySelector('input[name="montant_total[]"]');
    const payeInput = row.querySelector('input[name="montant_paye[]"]');
    const resteInput = row.querySelector('input[name="reste_a_payer[]"]');

    // Stocker la valeur initiale du reste (montant total au début)
    resteInput.dataset.initial = totalInput.value;

    payeInput.addEventListener("input", () => {
      const total = parseFloat(totalInput.value) || 0;
      let paye = parseFloat(payeInput.value) || 0;

      if (payeInput.value.startsWith("-")) {
        paye = 0;
        payeInput.value = "";
      }

      const newReste = Math.max(0, total - paye);
      resteInput.value = newReste.toFixed(0);

      checkPayments();
    });

    totalInput.addEventListener("input", () => {
      const total = parseFloat(totalInput.value) || 0;
      const paye = parseFloat(payeInput.value) || 0;
      resteInput.value = Math.max(0, total - paye).toFixed(0);
      checkPayments();
    });
  });

  form.addEventListener("input", checkPayments);
  checkPayments(); // État initial
});
