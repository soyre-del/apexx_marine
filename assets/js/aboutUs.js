document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Map
    const map = L.map('global-map', {
        minZoom: 2,
        maxZoom: 6,
        zoomSnap: 0.25
    }).setView([20.0, 10.0], 2);

    // Force map container background color to eliminate default grey loading states
    document.getElementById('global-map').style.background = '#0d1b2a';

    // 2. LOAD OFFLINE MAP DATA (.json file)
    fetch('/apexx_marine/assets/data/world.json')
        .then(response => {
            if (!response.ok) {
                throw new Error("HTTP error! Status: " + response.status);
            }
            return response.json();
        })
        .then(data => {
            L.geoJSON(data, {
                style: {
                    color: "#4f6d85",       // Border color of countries (Brand Steel)
                    weight: 1,              // Border thickness
                    fillColor: "#0D2A4A",   // Fill color of countries (Brand Navy)
                    fillOpacity: 1
                }
            }).addTo(map);
        })
        .catch(error => console.error("Error loading offline map. Check your file path.", error));

    // 3. Add Apex Hubs with PERMANENT Labels
    const apexHubs = [
        { name: 'APAC Hub: Singapore', lat: 1.290270, lng: 103.851959 },
        { name: 'APAC Hub: Shanghai', lat: 31.230416, lng: 121.473701 },
        { name: 'EMEA Hub: Rotterdam', lat: 51.922500, lng: 4.479170 },
        { name: 'EMEA Hub: Dubai', lat: 25.204849, lng: 55.270783 },
        { name: 'EMEA Hub: Cape Town', lat: -33.924870, lng: 18.424055 },
        { name: 'Americas Hub: Houston', lat: 29.760427, lng: -95.369804 },
        { name: 'Americas Hub: Panama City', lat: 8.982379, lng: -79.519875 }
    ];

    // Loop through each hub and create a marker that includes both the dot and the text
    apexHubs.forEach(function (hub) {
        const customIcon = L.divIcon({
            className: 'custom-marker-with-label',
            html: `
                <div style="display: flex; align-items: center; width: max-content; pointer-events: none;">
                    <!-- The glowing dot -->
                    <div style="background-color: #F59E0B; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 10px rgba(245,158,11,0.8); flex-shrink: 0;"></div>
                    
                    <!-- The permanent text label -->
                    <span style="color: #ffffff; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-left: 8px; text-shadow: 0px 2px 4px rgba(0,0,0,0.9);">
                        ${hub.name}
                    </span>
                </div>
            `,
            iconSize: [12, 12], // Size of the anchor point
            iconAnchor: [6, 6]  // Centers the 12x12 dot perfectly on the coordinates
        });

        // Place the marker on the map
        L.marker([hub.lat, hub.lng], { icon: customIcon }).addTo(map);
    });
});