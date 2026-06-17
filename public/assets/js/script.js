const jobs = [
    {
        id: "job-001",
        title: "Monitoring and Evaluation Officer",
        companyName: "Rwanda Skills Development Board",
        sector: "Public Service",
        location: "Kigali",
        salaryRange: "RWF 850,000 - 1,200,000",
        postedDate: "2026-06-15",
        deadline: "2026-07-05",
        descriptionShort: "Support results tracking, reporting, and field data quality for workforce development programs.",
        descriptionFull: "The officer will coordinate monitoring plans, validate field reports, maintain performance dashboards, and prepare evidence-based summaries for employment and skills programs.",
        educationLevel: "Bachelor's degree",
        minExperienceYears: 3,
        skillsRequired: ["Data analysis", "Report writing", "Field coordination", "Excel"],
        otherRequirements: "Experience with public sector reporting frameworks and strong written English or Kinyarwanda.",
        companyAbout: "Rwanda Skills Development Board supports national workforce readiness and skills development initiatives."
    },
    {
        id: "job-002",
        title: "Software Support Analyst",
        companyName: "Kigali Digital Services Ltd",
        sector: "ICT",
        location: "Kigali",
        salaryRange: "RWF 700,000 - 950,000",
        postedDate: "2026-06-14",
        deadline: "2026-06-30",
        descriptionShort: "Provide user support, troubleshoot application issues, and document service requests for enterprise clients.",
        descriptionFull: "The analyst will manage user tickets, investigate software issues, produce support documentation, and coordinate with developers on recurring product concerns.",
        educationLevel: "Diploma",
        minExperienceYears: 2,
        skillsRequired: ["Help desk support", "SQL basics", "Documentation", "Customer service"],
        otherRequirements: "Comfortable explaining technical issues to non-technical users.",
        companyAbout: "Kigali Digital Services Ltd builds and supports business software for Rwandan institutions."
    },
    {
        id: "job-003",
        title: "Agricultural Extension Coordinator",
        companyName: "Green Hills Cooperative Union",
        sector: "Agriculture",
        location: "Musanze",
        salaryRange: "RWF 600,000 - 820,000",
        postedDate: "2026-06-12",
        deadline: "2026-07-01",
        descriptionShort: "Coordinate farmer training, crop advisory visits, and cooperative reporting across northern districts.",
        descriptionFull: "This role supports cooperative members through field visits, seasonal training plans, input tracking, and reporting on crop productivity improvements.",
        educationLevel: "Diploma",
        minExperienceYears: 3,
        skillsRequired: ["Farmer training", "Crop advisory", "Field reporting", "Cooperative management"],
        otherRequirements: "Motorbike riding license is an added advantage.",
        companyAbout: "Green Hills Cooperative Union works with smallholder farmers to improve market access and productivity."
    },
    {
        id: "job-004",
        title: "Secondary Mathematics Teacher",
        companyName: "Huye Excellence School",
        sector: "Education",
        location: "Huye",
        salaryRange: "RWF 480,000 - 650,000",
        postedDate: "2026-06-10",
        deadline: "2026-06-28",
        descriptionShort: "Teach lower and upper secondary mathematics while supporting assessment and learner mentorship.",
        descriptionFull: "The teacher will prepare lesson plans, deliver competency-based instruction, assess learner progress, and support academic clubs and mentorship activities.",
        educationLevel: "Bachelor's degree",
        minExperienceYears: 2,
        skillsRequired: ["Lesson planning", "Assessment", "Learner support", "CBC curriculum"],
        otherRequirements: "Teaching qualification or registration with relevant education authorities preferred.",
        companyAbout: "Huye Excellence School is a private secondary school focused on science and learner support."
    },
    {
        id: "job-005",
        title: "Branch Accountant",
        companyName: "Umurava Microfinance Plc",
        sector: "Finance",
        location: "Rubavu",
        salaryRange: "RWF 750,000 - 1,000,000",
        postedDate: "2026-06-09",
        deadline: "2026-06-27",
        descriptionShort: "Manage branch accounts, reconciliations, statutory reporting, and internal financial controls.",
        descriptionFull: "The branch accountant will prepare daily reconciliations, review transaction records, support audit requests, and maintain compliance with finance policies.",
        educationLevel: "Bachelor's degree",
        minExperienceYears: 3,
        skillsRequired: ["Accounting", "Reconciliation", "Financial reporting", "Internal controls"],
        otherRequirements: "CPA progress or strong microfinance experience is preferred.",
        companyAbout: "Umurava Microfinance Plc provides inclusive financial services to households and small enterprises."
    },
    {
        id: "job-006",
        title: "Community Health Program Assistant",
        companyName: "Rwanda Community Health Network",
        sector: "Healthcare",
        location: "Muhanga",
        salaryRange: "RWF 520,000 - 720,000",
        postedDate: "2026-06-08",
        deadline: "2026-06-25",
        descriptionShort: "Assist district health outreach activities, beneficiary follow-up, and monthly program documentation.",
        descriptionFull: "The assistant will coordinate community health meetings, compile beneficiary records, follow up referrals, and support monthly district reporting.",
        educationLevel: "Diploma",
        minExperienceYears: 2,
        skillsRequired: ["Community outreach", "Data collection", "Health education", "Reporting"],
        otherRequirements: "Experience working with community health workers is required.",
        companyAbout: "Rwanda Community Health Network supports outreach and preventive care programs across districts."
    },
    {
        id: "job-007",
        title: "Tourism Operations Supervisor",
        companyName: "Virunga Heritage Tours",
        sector: "Tourism",
        location: "Musanze",
        salaryRange: "RWF 650,000 - 900,000",
        postedDate: "2026-06-07",
        deadline: "2026-07-03",
        descriptionShort: "Supervise tour schedules, guide coordination, guest communication, and daily operations records.",
        descriptionFull: "The supervisor will coordinate guides, prepare guest itineraries, resolve operational issues, and ensure service standards are met across tours.",
        educationLevel: "Diploma",
        minExperienceYears: 3,
        skillsRequired: ["Operations", "Guest relations", "Scheduling", "Team coordination"],
        otherRequirements: "Fluency in English and Kinyarwanda is required; French is an advantage.",
        companyAbout: "Virunga Heritage Tours offers cultural and nature-based travel experiences in northern Rwanda."
    },
    {
        id: "job-008",
        title: "Procurement Assistant",
        companyName: "Eastern Infrastructure Partners",
        sector: "Construction",
        location: "Rwamagana",
        salaryRange: "RWF 500,000 - 680,000",
        postedDate: "2026-06-06",
        deadline: "2026-06-29",
        descriptionShort: "Support supplier sourcing, purchase documentation, bid comparison, and inventory coordination.",
        descriptionFull: "The assistant will maintain procurement files, request quotations, compare supplier submissions, and support contract and delivery follow-up.",
        educationLevel: "Diploma",
        minExperienceYears: 1,
        skillsRequired: ["Procurement", "Supplier records", "Inventory", "Excel"],
        otherRequirements: "Knowledge of procurement procedures and ethical sourcing standards.",
        companyAbout: "Eastern Infrastructure Partners delivers civil works and infrastructure support services."
    },
    {
        id: "job-009",
        title: "Nurse Team Lead",
        companyName: "Nyamirambo Family Clinic",
        sector: "Healthcare",
        location: "Kigali",
        salaryRange: "RWF 780,000 - 1,050,000",
        postedDate: "2026-06-05",
        deadline: "2026-07-02",
        descriptionShort: "Lead nursing shifts, patient triage, clinical records, and care quality checks.",
        descriptionFull: "The nurse team lead will supervise daily nursing work, maintain patient care standards, coordinate handovers, and support clinic quality reviews.",
        educationLevel: "Bachelor's degree",
        minExperienceYears: 4,
        skillsRequired: ["Clinical supervision", "Patient care", "Triage", "Records management"],
        otherRequirements: "Valid nursing license is required.",
        companyAbout: "Nyamirambo Family Clinic provides outpatient, maternal, and preventive health services."
    },
    {
        id: "job-010",
        title: "District Employment Advisor",
        companyName: "Youth Opportunity Center",
        sector: "Public Service",
        location: "Nyagatare",
        salaryRange: "RWF 620,000 - 840,000",
        postedDate: "2026-06-03",
        deadline: "2026-06-24",
        descriptionShort: "Advise job seekers, organize employer clinics, and track placement outcomes at district level.",
        descriptionFull: "The advisor will counsel job seekers, coordinate employer engagement sessions, maintain placement records, and support employment readiness activities.",
        educationLevel: "Bachelor's degree",
        minExperienceYears: 2,
        skillsRequired: ["Career guidance", "Employer engagement", "Case management", "Reporting"],
        otherRequirements: "Experience working with youth employment programs is preferred.",
        companyAbout: "Youth Opportunity Center connects young people with training, coaching, and employment pathways."
    },
    {
        id: "job-011",
        title: "Warehouse and Logistics Officer",
        companyName: "Lake Kivu Foods Ltd",
        sector: "Logistics",
        location: "Rubavu",
        salaryRange: "RWF 580,000 - 760,000",
        postedDate: "2026-06-01",
        deadline: "2026-06-22",
        descriptionShort: "Coordinate warehouse stock, dispatch planning, supplier deliveries, and logistics records.",
        descriptionFull: "The officer will supervise warehouse movements, update stock records, plan dispatches, and coordinate deliveries with suppliers and customers.",
        educationLevel: "Diploma",
        minExperienceYears: 2,
        skillsRequired: ["Warehouse management", "Dispatch planning", "Stock control", "Logistics records"],
        otherRequirements: "Experience with food handling or FMCG distribution is an added advantage.",
        companyAbout: "Lake Kivu Foods Ltd processes and distributes food products to retail and institutional buyers."
    },
    {
        id: "job-012",
        title: "Human Resources Coordinator",
        companyName: "Muhanga Manufacturing Group",
        sector: "Manufacturing",
        location: "Muhanga",
        salaryRange: "RWF 720,000 - 980,000",
        postedDate: "2026-05-30",
        deadline: "2026-06-23",
        descriptionShort: "Coordinate recruitment files, staff records, onboarding, leave tracking, and HR reporting.",
        descriptionFull: "The coordinator will manage employee records, support recruitment and onboarding, prepare HR reports, and assist with policy communication.",
        educationLevel: "Bachelor's degree",
        minExperienceYears: 3,
        skillsRequired: ["Recruitment", "Employee records", "HR reporting", "Policy communication"],
        otherRequirements: "Knowledge of Rwanda labor law is required.",
        companyAbout: "Muhanga Manufacturing Group produces household goods and supports local supply chain development."
    },
    {
        id: "job-013",
        title: "Junior Credit Officer",
        companyName: "Agaciro Finance Cooperative",
        sector: "Finance",
        location: "Huye",
        salaryRange: "RWF 450,000 - 620,000",
        postedDate: "2026-05-28",
        deadline: "2026-06-21",
        descriptionShort: "Review loan applications, conduct client visits, and support portfolio monitoring.",
        descriptionFull: "The credit officer will screen applications, verify client information, monitor repayments, and prepare loan committee documentation.",
        educationLevel: "Diploma",
        minExperienceYears: 1,
        skillsRequired: ["Credit analysis", "Client visits", "Portfolio monitoring", "Documentation"],
        otherRequirements: "Strong numeracy and integrity checks are required.",
        companyAbout: "Agaciro Finance Cooperative provides savings and credit services for local entrepreneurs."
    },
    {
        id: "job-014",
        title: "ICT Lab Technician",
        companyName: "Rwanda Polytechnic Training Center",
        sector: "ICT",
        location: "Huye",
        salaryRange: "RWF 540,000 - 730,000",
        postedDate: "2026-05-26",
        deadline: "2026-06-20",
        descriptionShort: "Maintain computer labs, support learners, and manage equipment readiness for practical sessions.",
        descriptionFull: "The technician will maintain lab equipment, install approved software, support practical classes, and keep accurate inventory and maintenance logs.",
        educationLevel: "Diploma",
        minExperienceYears: 2,
        skillsRequired: ["Hardware support", "Network basics", "Software installation", "Inventory"],
        otherRequirements: "Technical certification in IT support is preferred.",
        companyAbout: "Rwanda Polytechnic Training Center provides applied technical and vocational education."
    }
];

const state = {
    search: "",
    sector: "",
    education: "",
    page: 1,
    pageSize: 12
};

const listingsEl = document.querySelector("#job-listings");
const paginationEl = document.querySelector("#pagination");
const resultCountEl = document.querySelector("#result-count");
const searchInput = document.querySelector("#job-search");
const sectorFilter = document.querySelector("#sector-filter");
const educationFilter = document.querySelector("#education-filter");
const modal = document.querySelector("#job-modal");
const modalCard = modal?.querySelector(".modal-card");
const applyButton = document.querySelector("#apply-button");
const applyMessage = document.querySelector("#apply-message");
const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

const sortedJobs = [...jobs].sort((a, b) => new Date(b.postedDate) - new Date(a.postedDate));

function uniqueValues(key) {
    return [...new Set(sortedJobs.map((job) => job[key]))].sort((a, b) => a.localeCompare(b));
}

function populateFilters() {
    uniqueValues("sector").forEach((sector) => {
        const option = document.createElement("option");
        option.value = sector;
        option.textContent = sector;
        sectorFilter.append(option);
    });

    uniqueValues("educationLevel").forEach((level) => {
        const option = document.createElement("option");
        option.value = level;
        option.textContent = level;
        educationFilter.append(option);
    });
}

function filteredJobs() {
    return sortedJobs.filter((job) => {
        const searchText = `${job.title} ${job.location}`.toLowerCase();
        const matchesSearch = searchText.includes(state.search.toLowerCase());
        const matchesSector = !state.sector || job.sector === state.sector;
        const matchesEducation = !state.education || job.educationLevel === state.education;
        return matchesSearch && matchesSector && matchesEducation;
    });
}

function renderJobs() {
    const matches = filteredJobs();
    const totalPages = Math.max(1, Math.ceil(matches.length / state.pageSize));
    state.page = Math.min(state.page, totalPages);

    const start = (state.page - 1) * state.pageSize;
    const pageJobs = matches.slice(start, start + state.pageSize);

    resultCountEl.textContent = matches.length === 1 ? "1 result" : `${matches.length} results`;
    listingsEl.innerHTML = "";

    if (pageJobs.length === 0) {
        listingsEl.innerHTML = `
            <div class="empty-state">
                <h3>No matching jobs found</h3>
                <p>Try a different keyword, sector, or education level.</p>
            </div>
        `;
    } else {
        const fragment = document.createDocumentFragment();
        pageJobs.forEach((job) => fragment.append(createJobCard(job)));
        listingsEl.append(fragment);
    }

    renderPagination(matches.length, totalPages);
}

function createJobCard(job) {
    const article = document.createElement("article");
    article.className = "job-card";
    article.dataset.id = job.id;

    const button = document.createElement("button");
    button.className = "job-card-button";
    button.type = "button";
    button.setAttribute("aria-label", `View details for ${job.title}`);
    button.addEventListener("click", () => openJobModal(job));

    button.innerHTML = `
        <header class="job-card-header">
            <h3>${job.title}</h3>
            <p class="job-company">${job.companyName}</p>
        </header>
        <dl class="job-meta">
            <div>
                <dt>Location</dt>
                <dd>${job.location}</dd>
            </div>
            <div>
                <dt>Salary range</dt>
                <dd>${job.salaryRange}</dd>
            </div>
            <div>
                <dt>Posted</dt>
                <dd>${formatDate(job.postedDate)}</dd>
            </div>
            <div>
                <dt>Deadline</dt>
                <dd>${formatDate(job.deadline)}</dd>
            </div>
        </dl>
        <p class="job-excerpt">${job.descriptionShort}</p>
    `;

    article.append(button);
    return article;
}

function renderPagination(totalItems, totalPages) {
    paginationEl.innerHTML = "";

    if (totalItems === 0) {
        return;
    }

    const previous = createPageButton("Previous", state.page - 1, state.page === 1);
    paginationEl.append(previous);

    for (let page = 1; page <= totalPages; page += 1) {
        const button = createPageButton(String(page), page, false);
        if (page === state.page) {
            button.classList.add("is-current");
            button.setAttribute("aria-current", "page");
        }
        paginationEl.append(button);
    }

    const next = createPageButton("Next", state.page + 1, state.page === totalPages);
    paginationEl.append(next);
}

function createPageButton(label, page, disabled) {
    const button = document.createElement("button");
    button.className = "pagination-button";
    button.type = "button";
    button.textContent = label;
    button.disabled = disabled;
    button.addEventListener("click", () => {
        state.page = page;
        renderJobs();
        document.querySelector("#job-listings-title").scrollIntoView({
            behavior: prefersReducedMotion.matches ? "auto" : "smooth",
            block: "start"
        });
    });
    return button;
}

function openJobModal(job) {
    document.querySelector("#modal-sector").textContent = job.sector;
    document.querySelector("#modal-title").textContent = job.title;
    document.querySelector("#modal-company").textContent = job.companyName;
    document.querySelector("#modal-location").textContent = job.location;
    document.querySelector("#modal-salary").textContent = job.salaryRange;
    document.querySelector("#modal-posted").textContent = formatDate(job.postedDate);
    document.querySelector("#modal-deadline").textContent = formatDate(job.deadline);
    document.querySelector("#modal-description").textContent = job.descriptionFull;
    document.querySelector("#modal-education").textContent = job.educationLevel;
    document.querySelector("#modal-experience").textContent = `${job.minExperienceYears}+ years`;
    document.querySelector("#modal-other-requirements").textContent = job.otherRequirements;
    document.querySelector("#modal-company-about").textContent = job.companyAbout;

    const skillsEl = document.querySelector("#modal-skills");
    skillsEl.innerHTML = "";
    job.skillsRequired.forEach((skill) => {
        const tag = document.createElement("span");
        tag.className = "skill-tag";
        tag.textContent = skill;
        skillsEl.append(tag);
    });

    applyMessage.hidden = true;
    applyMessage.innerHTML = "";
    modal.hidden = false;
    document.body.style.overflow = "hidden";
    modalCard.focus();
}

function closeJobModal() {
    modal.hidden = true;
    document.body.style.overflow = "";
}

function formatDate(value) {
    return new Intl.DateTimeFormat("en-RW", {
        year: "numeric",
        month: "short",
        day: "numeric"
    }).format(new Date(`${value}T00:00:00`));
}

searchInput.addEventListener("input", (event) => {
    state.search = event.target.value.trim();
    state.page = 1;
    renderJobs();
});

sectorFilter.addEventListener("change", (event) => {
    state.sector = event.target.value;
    state.page = 1;
    renderJobs();
});

educationFilter.addEventListener("change", (event) => {
    state.education = event.target.value;
    state.page = 1;
    renderJobs();
});

modal?.addEventListener("click", (event) => {
    if (event.target.closest("[data-modal-close]")) {
        closeJobModal();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) {
        closeJobModal();
    }
});

applyButton?.addEventListener("click", () => {
    applyMessage.hidden = false;
    applyMessage.innerHTML = 'Please log in to apply for this job. <a href="login.html">Login</a>';
});

populateFilters();
renderJobs();
