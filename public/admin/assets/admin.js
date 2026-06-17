function initTabs() {
    const tabs = document.querySelectorAll("[data-tab]");
    const panels = document.querySelectorAll("[data-panel]");
    if (tabs.length === 0 || panels.length === 0) return;

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            const target = tab.dataset.tab;

            tabs.forEach((item) => {
                const isActive = item === tab;
                item.classList.toggle("is-active", isActive);
                item.setAttribute("aria-selected", String(isActive));
            });

            panels.forEach((panel) => {
                const isActive = panel.dataset.panel === target;
                panel.classList.toggle("is-active", isActive);
                panel.hidden = !isActive;
            });
        });
    });
}

function initApprovalPlaceholders() {
    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action]");
        if (!button) return;

        const item = button.closest(".approval-item");
        if (!item) return;

        const approved = button.dataset.action === "approved";
        item.classList.add("is-resolved");
        item.querySelector(".approval-actions").innerHTML = `<span class="status-tag ${approved ? "approved" : "pending"}">${approved ? "Approved" : "Rejected"}</span>`;
    });
}

function initReportPlaceholders() {
    document.querySelectorAll("[data-download]").forEach((button) => {
        button.addEventListener("click", () => {
            alert(`${button.dataset.download} download will be available after report generation is connected.`);
        });
    });
}

initTabs();
initApprovalPlaceholders();
initReportPlaceholders();
