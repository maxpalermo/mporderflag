function updateSelectFlag(selectStatusFlag) {
    const value = currentFlag.id_order_flag_item ?? 0;
    const color = currentFlag.color ?? "#70b580";
    $(selectStatusFlag).val(value).trigger("change");
    $(selectStatusFlag).closest("div.select-status").css("background-color", color);
}

async function updateFlag() {
    const value = document.getElementById("order-flag-combo").value;
    const response = await fetch(updateFlagAction, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({ flag: value })
    });
    const data = await response.json();
    if (data.status == true) {
        swalSuccess(data.message);
    } else {
        swalError(data.message);
    }
}

document.addEventListener("updateOrderFlag", async (e) => {
    const orderViewPage = document.getElementById("order-view-page");

    // Recupera i dati dal tag <script>
    const options = orderFlagOptions;
    const selectStatus = document.createElement("div");
    const selectFlag = document.createElement("select");

    selectFlag.setAttribute("id", "order-flag-combo");
    selectFlag.setAttribute("name", "order-flag-combo");

    selectStatus.classList.add("select-status");
    selectStatus.setAttribute("id", "select-status-flag");
    selectStatus.style.backgroundColor = "#70b580";
    selectStatus.style.color = "#fcfcfc";
    selectStatus.style.display = "flex";
    selectStatus.style.alignItems = "center";
    selectStatus.style.justifyContent = "start";
    selectStatus.style.height = "56px";
    selectStatus.style.borderRadius = "5px";
    selectStatus.style.border = "1px solid #70b580";
    selectStatus.style.marginBottom = "2rem";

    selectFlag.innerHTML = "";

    // Aggiungi le opzioni
    options.forEach((option) => {
        selectFlag.innerHTML += `
            <option value="${option.id}" data-color="${option.color}" data-icon="${option.icon}">${option.name}</option>
        `;
    });

    // Crea il div container
    selectStatus.appendChild(selectFlag);
    orderViewPage.insertAdjacentElement("afterbegin", selectStatus);

    // Inizializza Select2
    const selectStatusFlag = $(selectFlag).select2({
        dropdownAutoWidth: true, // Permetti al dropdown di espandersi
        templateResult: formatOption,
        templateSelection: formatOption,
        escapeMarkup: (m) => m // Disabilita escape per Material Icons
    });

    selectStatusFlag.next(".select2-container").css("margin-top", "24px");

    // Imposta il background del dropdown select2 a grigio chiaro
    selectStatusFlag.on("select2:open", function () {
        styleSelect2Options();
    });

    // Cambia dinamicamente il colore di background del container quando cambia opzione
    selectStatusFlag.on("select2:select", async (e) => {
        const color = $(e.params.data.element).data("color");
        $("#select-status-flag").css("background-color", color || "#70b580");
        await updateFlag();
    });

    function styleSelect2Options() {
        // Stili per le opzioni dei risultati
        $(".select2-results__option").css({
            "white-space": "nowrap",
            overflow: "hidden",
            "text-overflow": "ellipsis",
            "background-color": "#FCFCFC",
            color: "#303030",
            "font-size": "16px",
            "line-height": "16px"
        });

        // Stili aggiuntivi per il contenitore del dropdown (opzionale)
        $(".select2-dropdown").css({
            "min-width": "300px", // Adatta al contenitore padre
            "overflow-x": "hidden" // Previene scroll orizzontale
        });

        $(".select2-container").css("min-width", "300px");
    }

    function formatOption(option) {
        if (!option.id) return option.text;
        const icon = $(option.element).data("icon");
        const $wrapper = $("<div>");

        if (icon) {
            $wrapper.append(
                $("<i>").addClass("material-icons").text(icon).css({
                    "margin-right": "8px",
                    "vertical-align": "middle",
                    "font-size": "2em"
                })
            );
        }

        $wrapper.append(
            $("<span>").text(option.text).css({
                "vertical-align": "middle",
                "font-size": "2em"
            })
        );
        // Sfondo trasparente, nessun bordo o colore testo
        return $wrapper;
    }

    styleSelect2Options();
    updateSelectFlag(selectStatusFlag);
});
