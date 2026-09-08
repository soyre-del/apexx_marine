document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize the map in the 'global-map' div, set center coordinates and zoom level
    const map = L.map('global-map').setView([25.0, 10.0], 2);

    // 2. Load the free map tiles from OpenStreetMap (This provides all the labels and borders)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // 3. Define your Apex Marine Strategic Hubs
    const apexHubs = [
        { name: "APAC Hub: Singapore", lat: 1.290270, lng: 103.851959 },
        { name: "APAC Hub: Shanghai", lat: 31.230416, lng: 121.473701 },
        { name: "EMEA Hub: Rotterdam", lat: 51.922500, lng: 4.479170 },
        { name: "EMEA Hub: Dubai", lat: 25.204849, lng: 55.270783 },
        { name: "EMEA Hub: Cape Town", lat: -33.924870, lng: 18.424055 },
        { name: "Americas Hub: Houston", lat: 29.760427, lng: -95.369804 },
        { name: "Americas Hub: Panama City", lat: 8.982379, lng: -79.519875 }
    ];

    // 4. Drop markers for each hub and add click popups
    apexHubs.forEach(hub => {
        L.marker([hub.lat, hub.lng])
         .addTo(map)
         .bindPopup(`<strong>${hub.name}</strong>`);
    });
});