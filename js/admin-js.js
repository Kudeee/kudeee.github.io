const content = document.getElementById("content");
const links = document.querySelectorAll(".nav a");

function loadPage(page) {
  fetch(`Admin-pages/${page}.html`)
    .then(res => res.text()).then(html => {
      document.getElementById("content").innerHTML = html;
    })

  // Dynamic interaction for Members page
  if (page === "members") {
    const btn = document.getElementById("addMemberBtn");
    const table = document.getElementById("membersTable");
    btn.addEventListener("click", () => {
      const name = prompt("Enter member name");
      if (name) {
        const row = table.insertRow();
        row.innerHTML = `<td>${name}</td><td>Active</td><td>Standard</td>`;
      }
    });
  }

  // Dynamic interaction for Events page
  if (page === "events") {
    const btn = document.getElementById("createEvent");
    btn.addEventListener("click", () => {
      const name = document.getElementById("eventName").value;
      const date = document.getElementById("eventDate").value;
      if (name && date) alert(`Event created: ${name} on ${date}`);
    });
  }
}

links.forEach((link) => {
  link.addEventListener("click", () => {
    links.forEach((l) => l.classList.remove("active"));
    link.classList.add("active");
    loadPage(link.dataset.page);
  });
});

// Load default page
loadPage("dashboard");
