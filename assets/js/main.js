// Variables globales para el mapa
let map;
let markers = [];

function initMap() {
  if (!document.getElementById("map")) return;

  // Coordenadas centradas para ver toda España incluyendo Canarias
  map = L.map("map", {
    center: [39.3, -6.0],
    zoom: 5,
    minZoom: 5,
    maxZoom: 18,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // Añadir marcadores para cada espacio
  const bounds = L.latLngBounds();
  document.querySelectorAll(".space-card").forEach((card) => {
    const lat = parseFloat(card.dataset.lat);
    const lng = parseFloat(card.dataset.lng);
    const id = card.dataset.id;
    const name = card.querySelector("h3 a").textContent;
    const city = card.querySelector(".location").textContent;
    const price =
      card.querySelector(".price")?.textContent.trim() || "Consultar precio";

    if (lat && lng) {
      const marker = L.marker([lat, lng]).addTo(map).bindPopup(`
                    <div class="map-popup">
                        <h3><a href="/space.php?id=${id}">${name}</a></h3>
                        <p>${city}</p>
                        <p class="price">${price}</p>
                        <a href="/space.php?id=${id}" class="button">Ver detalles</a>
                    </div>
                `);
      markers.push(marker);
      bounds.extend([lat, lng]);

      // Resaltar card al hacer hover en el marcador
      marker.on("mouseover", () => {
        card.classList.add("highlight");
      });
      marker.on("mouseout", () => {
        card.classList.remove("highlight");
      });
    }
  });

  // Si hay marcadores, ajustar el mapa para mostrarlos todos
  if (markers.length > 0) {
    map.fitBounds(bounds, {
      padding: [50, 50],
      maxZoom: 5, // Limitar el zoom máximo al hacer fit
    });
  } else {
    // Si no hay marcadores, mostrar toda España
    map.setView([39.3, -6.0], 5);
  }
}

// Resaltar marcador al hacer hover en la card
document.querySelectorAll(".space-card").forEach((card) => {
  card.addEventListener("mouseover", () => {
    const index = Array.from(card.parentElement.children).indexOf(card);
    if (markers[index]) {
      markers[index].openPopup();
    }
  });
  card.addEventListener("mouseout", () => {
    const index = Array.from(card.parentElement.children).indexOf(card);
    if (markers[index]) {
      markers[index].closePopup();
    }
  });
});

// Inicializar mapa cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", initMap);
