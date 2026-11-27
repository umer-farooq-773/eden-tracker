<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" />
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<style>
    .maplibregl-popup-content {
        background: #0f172a !important;
        color: #f8fafc !important;
        border-radius: 0.75rem !important;
        padding: 0.85rem !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(51, 65, 85, 0.8) !important;
        font-family: inherit !important;
    }
    .maplibregl-popup-anchor-top .maplibregl-popup-tip { border-bottom-color: #0f172a !important; }
    .maplibregl-popup-anchor-bottom .maplibregl-popup-tip { border-top-color: #0f172a !important; }
    .maplibregl-popup-anchor-left .maplibregl-popup-tip { border-right-color: #0f172a !important; }
    .maplibregl-popup-anchor-right .maplibregl-popup-tip { border-left-color: #0f172a !important; }
    .maplibregl-popup-close-button {
        color: #94a3b8 !important;
        padding: 4px 8px !important;
        font-size: 16px !important;
    }
    .maplibregl-popup-close-button:hover {
        color: #ffffff !important;
        background: transparent !important;
    }
    @keyframes mapPulse {
        0% { transform: scale(0.95); opacity: 0.9; }
        50% { transform: scale(1.4); opacity: 0.3; }
        100% { transform: scale(0.95); opacity: 0.9; }
    }
    .map-pulse-ring {
        animation: mapPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    #gps-map-modal > div {
        height: 100dvh;
        min-height: 0;
        overflow: hidden;
    }
    #gps-map-modal > div > div:first-child {
        flex: 0 0 auto;
        max-height: 45dvh;
        overflow-y: auto;
        overscroll-behavior: contain;
    }
    #gps-map-modal > div > div:last-child {
        min-height: 0;
        overflow: hidden;
    }
    #maplibre-canvas {
        position: absolute;
        inset: 0;
    }
</style>
