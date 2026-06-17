const adminData = {
    activity: [
        { event: "New employer registered - Kivu Freight Ltd.", time: "2 hours ago", status: "Pending" },
        { event: "Vacancy posted - Logistics Officer", time: "3 hours ago", status: "Pending" },
        { event: "Exchange request sent - Amahoro Textiles to Nyungwe Mills", time: "5 hours ago", status: "Pending" },
        { event: "Employer approved - Kigali Digital Services Ltd", time: "Yesterday", status: "Approved" },
        { event: "Job seeker profile verified - Jean N.", time: "Yesterday", status: "Completed" },
        { event: "Vacancy approved - Mathematics Teacher", time: "2 days ago", status: "Approved" },
        { event: "Exchange completed - Muhanga Manufacturing Group", time: "2 days ago", status: "Completed" },
        { event: "Report generated - Vacancy demand by sector", time: "3 days ago", status: "Completed" },
        { event: "Employer update submitted - Lake Kivu Foods Ltd", time: "3 days ago", status: "Pending" }
    ],
    approvals: {
        employers: [
            { title: "Kivu Freight Ltd.", detail: "Logistics", meta: "Submitted Jun 17, 2026" },
            { title: "Amahoro Textiles", detail: "Manufacturing", meta: "Submitted Jun 16, 2026" },
            { title: "Nyungwe Mills", detail: "Agriculture", meta: "Submitted Jun 16, 2026" },
            { title: "Huye Care Clinic", detail: "Healthcare", meta: "Submitted Jun 15, 2026" },
            { title: "Eastern Bridge Works", detail: "Construction", meta: "Submitted Jun 14, 2026" }
        ],
        jobs: [
            { title: "Logistics Officer", detail: "Kivu Freight Ltd.", meta: "Submitted Jun 17, 2026" },
            { title: "Production Supervisor", detail: "Amahoro Textiles", meta: "Submitted Jun 16, 2026" },
            { title: "Agronomy Field Lead", detail: "Nyungwe Mills", meta: "Submitted Jun 15, 2026" },
            { title: "Clinic Records Assistant", detail: "Huye Care Clinic", meta: "Submitted Jun 15, 2026" },
            { title: "Site Procurement Clerk", detail: "Eastern Bridge Works", meta: "Submitted Jun 14, 2026" }
        ],
        exchanges: [
            { title: "Kivu Freight Ltd. to Lake Kivu Foods", detail: "Employee: Diane U.", meta: "Paid", type: "Paid" },
            { title: "Amahoro Textiles to Nyungwe Mills", detail: "Employee: Eric M.", meta: "Swap", type: "Swap" },
            { title: "Huye Care Clinic to Muhanga District Clinic", detail: "Employee: Alice I.", meta: "Paid", type: "Paid" },
            { title: "Eastern Bridge Works to Rwamagana Roads Unit", detail: "Employee: Patrick N.", meta: "Swap", type: "Swap" },
            { title: "Kigali Digital Services to Youth Opportunity Center", detail: "Employee: Grace K.", meta: "Paid", type: "Paid" }
        ]
    },
    reportBars: [
        { label: "ICT", value: 18 },
        { label: "Healthcare", value: 14 },
        { label: "Finance", value: 12 },
        { label: "Education", value: 10 },
        { label: "Agriculture", value: 8 },
        { label: "Logistics", value: 6 }
    ]
};

const page = document.body.dataset.adminPage;

function statusClass(status) {
    return status.toLowerCase();
}

function initDashboard() {
    const list = document.querySelector("#activity-list");
    if (!list) return;

    list.innerHTML = adminData.activity.map((item) => `
        <article class="activity-row">
            <p>${item.event}</p>
            <time>${item.time}</time>
            <span class="status-tag ${statusClass(item.status)}">${item.status}</span>
        </article>
    `).join("");
}

function initApprovals() {
    const list = document.querySelector("#approval-list");
    const tabs = document.querySelectorAll("[data-tab]");
    if (!list || tabs.length === 0) return;

    let activeTab = "employers";

    function renderApprovals() {
        list.innerHTML = adminData.approvals[activeTab].map((item, index) => {
            const badge = activeTab === "exchanges" ? `<span class="type-badge">${item.type}</span>` : "";
            return `
                <article class="approval-item" data-index="${index}">
                    <div class="approval-primary">
                        <p>${item.title}</p>
                        <span class="approval-meta">${item.detail} · ${item.meta}</span>
                        ${badge}
                    </div>
                    <div class="approval-actions">
                        <button class="button-primary" type="button" data-action="approved">Approve</button>
                        <button class="button-outline reject" type="button" data-action="rejected">Reject</button>
                    </div>
                </article>
            `;
        }).join("");
    }

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            activeTab = tab.dataset.tab;
            tabs.forEach((item) => {
                const isActive = item === tab;
                item.classList.toggle("is-active", isActive);
                item.setAttribute("aria-selected", String(isActive));
            });
            renderApprovals();
        });
    });

    list.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action]");
        if (!button) return;

        const item = button.closest(".approval-item");
        const action = button.dataset.action;
        item.classList.add("is-resolved");
        item.querySelector(".approval-actions").innerHTML = `<span class="status-tag ${action === "approved" ? "approved" : "pending"}">${action === "approved" ? "Approved" : "Rejected"}</span>`;
    });

    renderApprovals();
}

function initReports() {
    const chart = document.querySelector("#bar-chart");
    const generate = document.querySelector("#generate-report");
    const message = document.querySelector("#report-message");
    if (!chart) return;

    const maxValue = Math.max(...adminData.reportBars.map((item) => item.value));
    chart.innerHTML = adminData.reportBars.map((item) => {
        const width = Math.round((item.value / maxValue) * 100);
        return `
            <div class="bar-row">
                <span class="bar-label">${item.label}</span>
                <div class="bar-track" aria-hidden="true">
                    <div class="bar-fill" style="width: ${width}%"></div>
                </div>
                <span class="bar-value">${item.value}</span>
            </div>
        `;
    }).join("");

    generate?.addEventListener("click", () => {
        message.textContent = "Mock report refreshed for the selected filters.";
    });

    document.querySelectorAll("[data-download]").forEach((button) => {
        button.addEventListener("click", () => {
            alert(`${button.dataset.download} download is a placeholder in this static prototype.`);
        });
    });
}

initDashboard();
initApprovals();
initReports();
