const modal = document.querySelector("#job-modal");
const modalCard = modal?.querySelector(".modal-card");
const applyLink = document.querySelector("#apply-link");
const applyMessage = document.querySelector("#apply-message");

function openJobModal(job) {
    document.querySelector("#modal-sector").textContent = job.sector || "Opportunity";
    document.querySelector("#modal-title").textContent = job.title;
    document.querySelector("#modal-company").textContent = job.companyName;
    document.querySelector("#modal-location").textContent = job.location;
    document.querySelector("#modal-salary").textContent = job.salaryRange;
    document.querySelector("#modal-posted").textContent = job.postedDate;
    document.querySelector("#modal-deadline").textContent = job.deadline;
    document.querySelector("#modal-description").textContent = job.descriptionFull;
    document.querySelector("#modal-education").textContent = job.educationLevel;
    document.querySelector("#modal-field-of-study").textContent = job.fieldOfStudy || "Not specified";
    document.querySelector("#modal-experience").textContent = `${job.minExperienceYears}+ years`;
    document.querySelector("#modal-other-requirements").textContent = job.otherRequirements || "Not specified";
    document.querySelector("#modal-company-about").textContent = job.companyAbout || "No company description provided.";

    const skillsEl = document.querySelector("#modal-skills");
    skillsEl.innerHTML = "";
    (job.skillsRequired || []).forEach((skill) => {
        const tag = document.createElement("span");
        tag.className = "skill-tag";
        tag.textContent = skill;
        skillsEl.append(tag);
    });

    if (job.canApply && job.applyUrl) {
        applyLink.hidden = false;
        applyLink.href = job.applyUrl;
        applyMessage.hidden = true;
        applyMessage.innerHTML = "";
    } else {
        applyLink.hidden = true;
        applyMessage.hidden = false;
        applyMessage.innerHTML = 'Please log in as a job seeker to apply for this job. <a href="login.php">Login</a>';
    }

    modal.hidden = false;
    document.body.style.overflow = "hidden";
    modalCard.focus();
}

function closeJobModal() {
    modal.hidden = true;
    document.body.style.overflow = "";
}

document.querySelectorAll("[data-job]").forEach((button) => {
    button.addEventListener("click", () => {
        openJobModal(JSON.parse(button.dataset.job));
    });
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
