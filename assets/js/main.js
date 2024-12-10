// Variables globales para el mapa
let searchMap = null;
let searchMarkers = [];

function initSearchMap() {
  // Verificar si estamos en la página de creación de espacios
  if (document.querySelector(".space-form")) {
    return;
  }

  const mapContainer = document.getElementById("map");
  if (!mapContainer) return;

  // Verificar si el contenedor del mapa ya tiene un mapa inicializado
  if (mapContainer._leaflet_id) {
    return; // Si ya está inicializado, no hacemos nada
  }

  // Coordenadas centradas para ver toda España incluyendo Canarias
  searchMap = L.map("map", {
    center: [39.3, -6.0],
    zoom: 5,
    minZoom: 5,
    maxZoom: 18,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(searchMap);

  // Limpiar marcadores existentes
  searchMarkers.forEach((marker) => marker.remove());
  searchMarkers = [];

  // Añadir marcadores para cada espacio
  const bounds = L.latLngBounds();
  document.querySelectorAll(".space-card").forEach((card) => {
    const lat = parseFloat(card.dataset.lat);
    const lng = parseFloat(card.dataset.lng);
    const id = card.dataset.id;
    const name = card.querySelector("h3 a").textContent;
    const city = card.querySelector(".location").textContent;

    if (lat && lng) {
      const marker = L.marker([lat, lng]).addTo(searchMap).bindPopup(`
                    <div class="map-popup">
                        <h3><a href="/space.php?id=${id}">${name}</a></h3>
                        <p>${city}</p>
                      
                        <a href="/space.php?id=${id}" class="button">Ver detalles</a>
                    </div>
                `);
      searchMarkers.push(marker);
      bounds.extend([lat, lng]);

      marker.on("mouseover", () => {
        card.classList.add("highlight");
      });
      marker.on("mouseout", () => {
        card.classList.remove("highlight");
      });
    }
  });

  if (searchMarkers.length > 0) {
    searchMap.fitBounds(bounds, {
      padding: [50, 50],
      maxZoom: 5,
    });
  }
}

// Inicializar mapa cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", initSearchMap);
