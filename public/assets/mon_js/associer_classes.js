document.addEventListener("DOMContentLoaded", function () {
  const ecoleSelect = document.getElementById("select-ecole");
  const classesSelect = document.getElementById("select-classes-ecole");
  const classesOfficiellesSelect = document.getElementById(
    "select-classes-officielles"
  );
  const loader = document.getElementById("ecole-loader");
  const matchInfo = document.getElementById("match-info");
  const submitBtn = document.getElementById("submit-btn");

  const ordreMapping = {
    fondamentale1: ["fondamentale i", "fondamentale 1", "fondamentale1"],
    fondamentale2: ["fondamentale ii", "fondamentale 2", "fondamentale2"],
    secondairegenerale: ["secondaire generale", "secondairegenerale"],
    secondairetechniqueetprofessionnel: [
      "secondaire technique et professionnel",
      "secondairetechniqueetprofessionnel",
    ],
  };

  function normalizeOrdre(ordre) {
    if (!ordre) return "";
    const ordreNorm = ordre
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "") // retire accents
      .replace(/\s+/g, "") // retire espaces
      .toLowerCase(); // minuscules
    for (const [key, variants] of Object.entries(ordreMapping)) {
      if (variants.includes(ordreNorm)) return key;
    }
    return ordreNorm;
  }


  function normalizeString(str) {
    return str
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, "")
      .toLowerCase();
  }

  $(".select2-multiple").select2({
    placeholder: "Sélectionnez des options",
    closeOnSelect: false,
    width: "100%",
  });

  ecoleSelect.addEventListener("change", function () {
    const idEcole = this.value;
    if (idEcole) {
      loadClassesEcole(idEcole);
    } else {
      resetForm();
    }
  });

  function loadClassesEcole(idEcole) {
    loader.classList.remove("d-none");
    $(classesSelect).prop("disabled", false).empty();

    fetch(`${root}/Classes_officielles/GetClassesByEcole/${idEcole}`)
      .then((response) => response.json())
      .then((data) => {
        if (Array.isArray(data) && data.length > 0) {
          data.forEach((classe) => {
            const option = new Option(
              `${classe.nom_classe} (${classe.ordreEnseignement})`,
              classe.id_classe
            );
            option.dataset.nom = classe.nom_classe.toLowerCase();
            option.dataset.ordre = classe.ordreEnseignement;
            classesSelect.add(option);
          });
        } else {
          classesSelect.add(
            new Option("Aucune classe trouvée", "", true, true)
          );
        }
      })
      .catch((error) => {
        console.error("Erreur lors du chargement des classes:", error);
        classesSelect.add(new Option("Erreur de chargement", "", true, true));
      })
      .finally(() => {
        loader.classList.add("d-none");
        $(classesSelect).trigger("change.select2");
      });
  }

  $("#select-classes-ecole").on("change", function () {
    const selected = Array.from(this.selectedOptions);
    if (selected.length > 0 && selected[0].value) {
      updateClassesOfficielles(selected);
    } else {
      resetOfficiellesSelect();
    }
  });

  function normalizeString(str) {
    return str
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, "")
      .toLowerCase();
  }

  function replaceNumbers(str) {
    return str
      .replace(/1|i\b/gi, "1")
      .replace(/2|ii\b/gi, "2")
      .replace(/3|iii\b/gi, "3");
  }

  function normalizeOrdreFull(str) {
    return replaceNumbers(normalizeString(str));
  }
  console.log("[DEBUG] updateClassesOfficielles called with:", selectedClasses);
   function extractClassNumber(classeName) {
     // Cherche le premier nombre dans le nom (7ème, 7e, 7, etc.)
     const match = classeName.match(/(\d{1,2})(?:ème|e|\b)/);
     return match ? parseInt(match[1]) : null;
   }

   function normalizeClassType(ordre) {
     if (!ordre) return "";
     const ordreNorm = ordre
       .normalize("NFD")
       .replace(/[\u0300-\u036f]/g, "")
       .replace(/\s+/g, "")
       .toLowerCase();

     // Simplifie la détection du type de classe
     if (ordreNorm.includes("fondamentale") || ordreNorm.includes("fonda")) {
       return ordreNorm.includes("2") || ordreNorm.includes("ii")
         ? "fondamentale2"
         : "fondamentale1";
     }
     if (ordreNorm.includes("secondaire")) {
       return ordreNorm.includes("technique")
         ? "secondairetechnique"
         : "secondairegenerale";
     }
     return ordreNorm;
   }

   function updateClassesOfficielles(selectedClasses) {
     $(classesOfficiellesSelect).prop("disabled", false);
     let hasMatches = false;

     Array.from(classesOfficiellesSelect.options).forEach((option) => {
       if (!option.value) return;
       option.disabled = true;
       option.classList.remove("match");
       option.selected = false;

       const optionNumber = extractClassNumber(option.dataset.nom);
       const optionType = normalizeClassType(option.dataset.ordre);

       for (const classe of selectedClasses) {
         const classeNumber = extractClassNumber(classe.dataset.nom);
         const classeType = normalizeClassType(classe.dataset.ordre);

         console.log(
           `Comparaison : classe "${classeNumber} | ${classeType}" vs option "${optionNumber} | ${optionType}"`
         );

         // Correspondance si même chiffre et même type
         if (classeNumber === optionNumber && classeType === optionType) {
           option.disabled = false;
           option.classList.add("match");
           option.selected = true;
           hasMatches = true;
           break;
         }
       }
     });

     updateMatchInfo(hasMatches);
     $(classesOfficiellesSelect).trigger("change");
     checkValidSelection();
   }


  // function updateClassesOfficielles(selectedClasses) {
  //   $(classesOfficiellesSelect).prop("disabled", false);
  //   let hasMatches = false;

  //   Array.from(classesOfficiellesSelect.options).forEach((option) => {
  //     if (!option.value) return;
  //     option.disabled = true;
  //     option.classList.remove("match");
  //     option.selected = false;

  //     for (const classe of selectedClasses) {
  //       const classeNom = normalizeString(classe.dataset.nom);
  //       const classeOrdre = normalizeString(classe.dataset.ordre); // normalisé côté PHP

  //       const optionNom = normalizeString(option.dataset.nom);
  //       const optionOrdre = normalizeString(option.dataset.ordre);

  //       console.log(
  //         `Comparaison : classe "${classeNom} | ${classeOrdre}" vs option "${optionNom} | ${optionOrdre}"`
  //       );

  //       if (classeNom === optionNom && classeOrdre === optionOrdre) {
  //         option.disabled = false;
  //         option.classList.add("match");
  //         option.selected = true;
  //         hasMatches = true;
  //         break;
  //       }
  //     }
    
  //   });

  //   updateMatchInfo(hasMatches);
  //   $(classesOfficiellesSelect).trigger("change");
  //   checkValidSelection();
  // }

  function updateMatchInfo(hasMatches) {
    if (hasMatches) {
      matchInfo.innerHTML =
        '<i class="fas fa-check-circle text-success me-2"></i>Correspondances trouvées';
      matchInfo.className = "mt-2 small text-success";
    } else {
      matchInfo.innerHTML =
        '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Aucune correspondance exacte';
      matchInfo.className = "mt-2 small text-warning";
    }
  }

  function checkValidSelection() {
    const valid =
      ecoleSelect.value &&
      $(classesSelect).val()?.length > 0 &&
      $(classesOfficiellesSelect).val()?.length > 0;
    console.log(
      `Validation: Ecole=${!!ecoleSelect.value}, Classes=${
        $(classesSelect).val()?.length
      }, Officielles=${$(classesOfficiellesSelect).val()?.length}`
    );
    submitBtn.disabled = !valid;
  }

  function resetForm() {
    $(classesSelect)
      .prop("disabled", true)
      .empty()
      .append(
        '<option value="" disabled>Sélectionnez une école d\'abord</option>'
      );
    resetOfficiellesSelect();
  }

  function resetOfficiellesSelect() {
    $(classesOfficiellesSelect)
      .prop("disabled", true)
      .val(null)
      .trigger("change.select2");
    matchInfo.innerHTML =
      '<i class="fas fa-info-circle me-2"></i>😍Sélectionnez des classes pour voir les correspondances';
    matchInfo.className = "mt-2 small text-muted";
    submitBtn.disabled = true;
  }

  resetForm();
});
