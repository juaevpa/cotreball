let map;
let markers = {};

// Inicializar el mapa cuando se carga la página
document.addEventListener("DOMContentLoaded", function () {
  // Crear el mapa centrado en España
  map = L.map("map").setView([40.4637, -3.7492], 6);

  // Añadir capa de OpenStreetMap
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // Inicializar marcadores desde las tarjetas existentes
  initializeMarkersFromCards();
  setupSearch();
});

function initializeMarkersFromCards() {
  document.querySelectorAll(".space-card").forEach((card) => {
    const spaceId = card.dataset.id;
    const name = card.querySelector("h3").textContent;
    const lat = parseFloat(card.dataset.lat);
    const lng = parseFloat(card.dataset.lng);

    // Crear marcador
    const marker = L.marker([lat, lng])
      .bindPopup(
        `
                <strong>${name}</strong>
              `
      )
      .addTo(map);

    markers[spaceId] = marker;

    // Añadir evento click al marcador
    marker.on("click", () => highlightSpace(spaceId));
  });
}

function highlightSpace(spaceId) {
  // Remover highlight de todas las cards
  document.querySelectorAll(".space-card").forEach((card) => {
    card.classList.remove("highlighted");
  });

  // Añadir highlight a la card seleccionada
  const card = document.querySelector(`.space-card[data-id="${spaceId}"]`);
  if (card) {
    card.classList.add("highlighted");
    card.scrollIntoView({ behavior: "smooth" });
  }
}

function setupSearch() {
  const searchInput = document.getElementById("searchInput");

  searchInput.addEventListener("input", (e) => {
    const searchTerm = e.target.value.toLowerCase();

    document.querySelectorAll(".space-card").forEach((card) => {
      const name = card.querySelector("h3").textContent.toLowerCase();
      const city = card.querySelector(".location").textContent.toLowerCase();

      if (name.includes(searchTerm) || city.includes(searchTerm)) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  });
}
