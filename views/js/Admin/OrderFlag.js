// Dati degli stati (dal JSON fornito)
const FLAG_DATA = {
    1: {
        id: "1",
        name: "OK",
        icon: "verified",
        color: "#70b580",
    },
    2: {
        id: "2",
        name: "ATTENZIONE",
        icon: "warning",
        color: "#e9bd0c",
    },
    3: {
        id: "3",
        name: "ERRORE",
        icon: "error",
        color: "#f54c3e",
    },
    4: {
        id: "4",
        name: "VERIFICA PAGAMENTO",
        icon: "payment",
        color: "#25b9d7",
    },
};

// Web Component OrderFlag
class OrderFlag extends HTMLElement {
    static get observedAttributes() {
        return ["flag-id", "background-color", "icon", "label", "endpoint"];
    }

    static ICONS = {
        verified: '<svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14-4-4 1.41-1.41L11 13.17l5.59-5.59L18 9l-7 7z"/></svg>',
        warning: '<svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M1 21h22L12 2 1 21z"/><rect x="11" y="8.8" width="2" height="7.2" rx="1" fill="#000" opacity="0.75"/><circle cx="12" cy="18.2" r="1.15" fill="#000" opacity="0.75"/></svg>',
        error: '<svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
        payment: '<svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4H4V6h16v2zm0 10H4v-6h16v6z"/></svg>',
    };

    constructor() {
        super();
        this.attachShadow({ mode: "open" });

        // Stato interno
        this._orderId = this.getAttribute("order-id") || "";
        this._flagId = this.getAttribute("flag-id") || "1";
        this._backgroundColor = this.getAttribute("background-color") || "#70b580";
        this._icon = this.getAttribute("icon") || "verified";
        this._label = this.getAttribute("label") || "OK";
        this._endpoint = this.getAttribute("endpoint") || "";

        this.render();
        this.attachEvents();
    }

    renderIcon(name) {
        return OrderFlag.ICONS[name] || OrderFlag.ICONS.verified;
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    cursor: pointer;
                    transition: transform 0.2s;
                }
                
                :host(:hover) {
                    transform: scale(1.02);
                }
                
                .flag-container {
                    width: 100%;
                    font-size: 2em;
                    padding: 1rem;
                    margin: 0 auto;
                    text-align: center;
                    background-color: ${this._backgroundColor};
                    color: #FCFCFC;
                    border-radius: 8px;
                    box-sizing: border-box;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: background-color 0.3s;
                }

                .of-icon {
                    width: 1em;
                    height: 1em;
                    fill: currentColor;
                    display: inline-block;
                    vertical-align: -0.125em;
                }
                
                .flag-icon {
                    font-size: 1.5em;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    line-height: 1;
                }
                
                /* Modal as native dialog */
                dialog.modal-dialog {
                    border: none;
                    padding: 0;
                    margin: auto;
                    background: transparent;
                    z-index: 99999;
                }

                dialog.modal-dialog::backdrop {
                    background: rgba(0, 0, 0, 0.5);
                }
                
                .modal-content {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                }
                
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #f0f0f0;
                }
                
                .modal-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 1.5rem;
                }
                
                .modal-close {
                    cursor: pointer;
                    font-size: 28px;
                    color: #999;
                    transition: color 0.2s;
                }
                
                .modal-close:hover {
                    color: #333;
                }
                
                .flag-option {
                    display: flex;
                    align-items: center;
                    padding: 15px;
                    margin: 10px 0;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    border: 2px solid transparent;
                    background: #f8f9fa;
                }
                
                .flag-option:hover {
                    transform: translateX(5px);
                    border-color: #007bff;
                    background: #f1f3f5;
                }
                
                .flag-option.current {
                    border-color: #28a745;
                    background: #d4edda;
                }
                
                .flag-option .material-icons {
                    margin-right: 15px;
                    font-size: 28px;
                }

                .flag-option .flag-icon {
                    margin-right: 15px;
                    font-size: 28px;
                }
                
                .flag-option .flag-name {
                    flex: 1;
                    font-weight: bold;
                    font-size: 1.1rem;
                }
                
                .flag-option .flag-id {
                    color: #666;
                    font-size: 0.85rem;
                    background: #e9ecef;
                    padding: 2px 8px;
                    border-radius: 12px;
                }
                
                .current-badge {
                    background: #28a745;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    margin-left: 10px;
                }
                
                .loading {
                    opacity: 0.6;
                    pointer-events: none;
                }
                
                .spinner {
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    border: 3px solid rgba(255,255,255,.3);
                    border-radius: 50%;
                    border-top-color: white;
                    animation: spin 1s ease-in-out infinite;
                }
                
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>
            
            <div class="flag-container" id="flagContainer">
                <span class="flag-icon" id="flagIcon">${this.renderIcon(this._icon)}</span>
                <span id="flagLabel">${this._label}</span>
            </div>
            
            <!-- Modal -->
            <dialog class="modal-dialog" id="modalDialog">
                <div class="modal-content" id="modalContent">
                    <div class="modal-header">
                        <h3>Seleziona nuovo stato</h3>
                        <span class="modal-close" id="modalClose">&times;</span>
                    </div>
                    <div id="modalOptions" class="modal-options"></div>
                </div>
            </dialog>
        `;

        this.flagContainer = this.shadowRoot.getElementById("flagContainer");
        this.flagIcon = this.shadowRoot.getElementById("flagIcon");
        this.flagLabel = this.shadowRoot.getElementById("flagLabel");
        this.modalDialog = this.shadowRoot.getElementById("modalDialog");
        this.modalOptions = this.shadowRoot.getElementById("modalOptions");
        this.modalClose = this.shadowRoot.getElementById("modalClose");
    }

    attachEvents() {
        // Click sul flag apre il modal
        this.flagContainer.addEventListener("click", () => {
            this.openModal();
        });

        // Chiusura modal
        this.modalClose.addEventListener("click", () => {
            this.closeModal();
        });

        // Click fuori dal contenuto chiude (click sul backdrop del dialog => target è dialog)
        this.modalDialog.addEventListener("click", (e) => {
            if (e.target === this.modalDialog) {
                this.closeModal();
            }
        });

        // ESC / cancel
        this.modalDialog.addEventListener("cancel", (e) => {
            e.preventDefault();
            this.closeModal();
        });
    }

    openModal() {
        this.renderModalOptions();
        this.modalDialog.showModal();

        this.logEvent("modal-opened", { currentId: this._flagId });
    }

    closeModal() {
        if (this.modalDialog.open) {
            this.modalDialog.close();
        }
    }

    renderModalOptions() {
        let html = "";

        Object.values(FLAG_DATA).forEach((flag) => {
            const isCurrent = flag.id === this._flagId;
            const backgroundColor = flag.color;

            html += `
                <div class="flag-option ${isCurrent ? "current" : ""}" 
                        data-flag-id="${flag.id}"
                        data-flag-color="${flag.color}"
                        data-flag-icon="${flag.icon}"
                        data-flag-name="${flag.name}"
                        style="border-left: 4px solid ${backgroundColor};">
                    <span class="flag-icon" style="color: ${backgroundColor};">${this.renderIcon(flag.icon)}</span>
                    <span class="flag-name">${flag.name}</span>
                    <span class="flag-id">ID: ${flag.id}</span>
                    ${isCurrent ? '<span class="current-badge">Corrente</span>' : ""}
                </div>
            `;
        });

        this.modalOptions.innerHTML = html;

        // Aggiungi event listener alle opzioni
        this.modalOptions.querySelectorAll(".flag-option").forEach((option) => {
            option.addEventListener("click", async () => {
                const flagId = option.dataset.flagId;
                if (flagId !== this._flagId) {
                    await this.updateFlag(flagId);
                }
            });
        });
    }

    async updateFlag(newFlagId) {
        const newFlag = FLAG_DATA[newFlagId];
        if (!newFlag) return;

        // Mostra loading
        this.flagContainer.classList.add("loading");
        const prevIcon = this._icon;
        const prevLabel = this._label;
        this.flagIcon.innerHTML = '<span class="spinner"></span>';
        this.flagLabel.textContent = "Aggiornamento...";

        try {
            const idOrder = this.getAttribute("order-id") || this.dataset.idOrder || this.dataset.orderId || this.dataset.id_order || this.dataset.order_id || "";

            // Prepara dati per l'invio
            const updateData = {
                ajax: 1,
                action: "updateOrderFlag",
                id_order: idOrder,
                order_id: idOrder,
                flag_id: newFlagId,
                old_flag_id: this._flagId,
                name: newFlag.name,
                icon: newFlag.icon,
                color: newFlag.color,
                timestamp: new Date().toISOString(),
            };

            // Aggiungi data attributes
            for (let key in this.dataset) {
                updateData[`data_${key}`] = this.dataset[key];
            }

            const formData = new FormData();
            Object.entries(updateData).forEach(([key, value]) => {
                if (value === undefined || value === null) {
                    return;
                }
                formData.append(key, String(value));
            });

            // Invia richiesta AJAX
            const response = await fetch(this._endpoint || "https://httpbin.org/post", {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            // Aggiorna lo stato locale
            this._flagId = newFlagId;
            this._backgroundColor = newFlag.color;
            this._icon = newFlag.icon;
            this._label = newFlag.name;

            // Aggiorna la UI
            this.flagContainer.style.backgroundColor = newFlag.color;
            this.flagIcon.innerHTML = this.renderIcon(newFlag.icon);
            this.flagLabel.textContent = newFlag.name;

            // Chiudi il modal
            this.closeModal();

            // Emetti evento
            this.dispatchEvent(
                new CustomEvent("flag-changed", {
                    detail: updateData,
                    bubbles: true,
                    composed: true,
                }),
            );

            // Mostra notifica growl
            this.showGrowl("success", `Stato aggiornato a "${newFlag.name}"`);

            this.logEvent("flag-updated", updateData);
        } catch (error) {
            console.error("Errore aggiornamento:", error);

            // Ripristina UI
            this._icon = prevIcon;
            this._label = prevLabel;
            this.updateUIFromCurrentState();

            // Mostra errore
            this.showGrowl("error", `Errore: ${error.message}`);

            this.logEvent("flag-error", { error: error.message });
        } finally {
            this.flagContainer.classList.remove("loading");
            this.updateUIFromCurrentState();
        }
    }

    updateUIFromCurrentState() {
        this.flagContainer.style.backgroundColor = this._backgroundColor;
        this.flagIcon.innerHTML = this.renderIcon(this._icon);
        this.flagLabel.textContent = this._label;
    }

    showGrowl(type, message) {
        if (window.$.growl) {
            $.growl(
                {
                    message: message,
                },
                {
                    type: type,
                    duration: 3000,
                    placement: {
                        from: "top",
                        align: "center",
                    },
                },
            );
        } else {
            alert(message);
        }
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;

        switch (name) {
            case "flag-id":
                this._flagId = newValue;
                const flagData = FLAG_DATA[newValue];
                if (flagData) {
                    this._backgroundColor = flagData.color;
                    this._icon = flagData.icon;
                    this._label = flagData.name;
                    this.updateUIFromCurrentState();
                }
                break;

            case "background-color":
                this._backgroundColor = newValue;
                this.flagContainer.style.backgroundColor = newValue;
                break;

            case "icon":
                this._icon = newValue;
                this.flagIcon.innerHTML = this.renderIcon(newValue);
                break;

            case "label":
                this._label = newValue;
                this.flagLabel.textContent = newValue;
                break;

            case "endpoint":
                this._endpoint = newValue;
                break;
        }
    }

    logEvent(type, data) {
        this.dispatchEvent(
            new CustomEvent("flag-log", {
                detail: { type, data, timestamp: new Date().toLocaleTimeString() },
            }),
        );
    }

    // Metodi pubblici
    get currentFlag() {
        return {
            id: this._flagId,
            name: this._label,
            icon: this._icon,
            color: this._backgroundColor,
        };
    }

    setFlagById(flagId) {
        if (FLAG_DATA[flagId]) {
            this.updateFlag(flagId);
        }
    }
}

// Registrazione del componente
customElements.define("order-flag", OrderFlag);
