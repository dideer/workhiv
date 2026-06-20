function initTabs() {
    const tabLists = document.querySelectorAll(".tab-list");
    if (tabLists.length === 0) return;

    tabLists.forEach((tabList) => {
        const container = tabList.closest(".approvals-panel") || document;
        const tabs = tabList.querySelectorAll("[data-tab-target], [data-tab]");
        if (tabs.length === 0) return;

        tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                const targetId = tab.dataset.tabTarget;
                const targetPanel = tab.dataset.tab;
                const panels = container.querySelectorAll(".tab-panel");

                tabs.forEach((item) => {
                    const isActive = item === tab;
                    item.classList.toggle("is-active", isActive);
                    item.setAttribute("aria-selected", String(isActive));
                });

                panels.forEach((panel) => {
                    const isActive = (targetId && panel.id === targetId) || (targetPanel && panel.dataset.panel === targetPanel);
                    panel.classList.toggle("is-active", isActive);
                    panel.hidden = !isActive;
                });
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
