document.addEventListener("DOMContentLoaded", function() {
        const map = new jsVectorMap({
        selector: "#global-map",
        map: "world",
        backgroundColor: "transparent",
        zoomOnScroll: false, // prevents the page from getting stuck when scrolling over the map
        
        
        regionStyle: {
            initial: {
            fill: "rgba(255, 255, 255, 0.1)", 
            stroke: "rgba(255, 255, 255, 0.05)",
            strokeWidth: 0.5
            },
            hover: {
            fill: "rgba(255, 255, 255, 0.25)"
            }
        },

        // style the hub markers 
        markerStyle: {
            initial: {
            fill: "#00d2ff", // replace this hex with your var(--bs-brand-ocean) equivalent
            stroke: "#ffffff",
            strokeWidth: 1.5,
            r: 6 // marker size
            },
            hover: {
            r: 8,
            fill: "#ffcc00" // a caution/warning color on hover
            }
        },

        // coordinates for your strategic global infrastructure hubs
        markers: [
            { name: "Singapore (APAC Hub)", coords: [1.3521, 103.8198] },
            { name: "Shanghai (APAC Hub)", coords: [31.2304, 121.4737] },
            { name: "Rotterdam (EMEA Hub)", coords: [51.9225, 4.4791] },
            { name: "Dubai (EMEA Hub)", coords: [25.2048, 55.2708] },
            { name: "Cape Town (EMEA Hub)", coords: [-33.9249, 18.4241] },
            { name: "Houston (Americas Hub)", coords: [29.7604, -95.3698] },
            { name: "Panama City (Americas Hub)", coords: [8.9824, -79.5199] }
        ]
        });
    });