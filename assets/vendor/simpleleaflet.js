(function (global) {
    'use strict';

    const TILE_SIZE = 256;
    const MIN_ZOOM = 2;
    const MAX_ZOOM = 18;

    function project(lat, lon, zoom) {
        const sinLat = Math.sin(lat * Math.PI / 180);
        const scale = Math.pow(2, zoom);
        const x = (lon + 180) / 360 * scale * TILE_SIZE;
        const y = (0.5 - Math.log((1 + sinLat) / (1 - sinLat)) / (4 * Math.PI)) * scale * TILE_SIZE;
        return { x, y };
    }

    function unproject(x, y, zoom) {
        const scale = Math.pow(2, zoom);
        const lon = x / (scale * TILE_SIZE) * 360 - 180;
        const n = Math.PI - 2 * Math.PI * y / (scale * TILE_SIZE);
        const lat = (180 / Math.PI) * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
        return { lat, lon };
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    class TileLayer {
        constructor(urlTemplate, options = {}) {
            this.urlTemplate = urlTemplate;
            this.options = options;
        }

        addTo(map) {
            this._map = map;
            map._tileLayer = this;
            map._redraw();
            return this;
        }

        getTileUrl(x, y, z) {
            let url = this.urlTemplate.replace('{z}', z).replace('{x}', x).replace('{y}', y);
            if (url.indexOf('{s}') !== -1) {
                const subdomains = this.options.subdomains || ['a', 'b', 'c'];
                const index = Math.abs((x + y) % subdomains.length);
                url = url.replace('{s}', subdomains[index]);
            }
            return url;
        }
    }

    class LayerGroup {
        constructor() {
            this._layers = [];
            this._map = null;
        }

        addLayer(layer) {
            this._layers.push(layer);
            layer._group = this;
            if (this._map) {
                this._map._redrawMarkers();
            }
            return this;
        }

        clearLayers() {
            this._layers = [];
            if (this._map) {
                this._map._redrawMarkers();
            }
        }

        addTo(map) {
            this._map = map;
            map._layerGroups.push(this);
            map._redrawMarkers();
            return this;
        }
    }

    class Marker {
        constructor(latlng, options = {}) {
            this.latlng = { lat: latlng[0], lon: latlng[1] };
            this.options = options;
            this.popupContent = null;
            this._element = null;
        }

        addTo(layer) {
            layer.addLayer(this);
            return this;
        }

        bindPopup(content) {
            this.popupContent = content;
            return this;
        }
    }

    class MapInstance {
        constructor(elementId, options = {}) {
            const element = typeof elementId === 'string' ? document.getElementById(elementId) : elementId;
            if (!element) {
                throw new Error('Map container not found');
            }
            this.container = element;
            this.container.classList.add('simple-leaflet-map');

            this.options = Object.assign({
                center: [0, 0],
                zoom: 4
            }, options);

            this.center = { lat: this.options.center[0], lon: this.options.center[1] };
            this.zoom = clamp(this.options.zoom, MIN_ZOOM, MAX_ZOOM);
            this._tileLayer = null;
            this._layerGroups = [];

            this._tilePane = document.createElement('div');
            this._tilePane.className = 'simple-leaflet-tiles';
            this.container.appendChild(this._tilePane);

            this._markerPane = document.createElement('div');
            this._markerPane.className = 'simple-leaflet-markers';
            this.container.appendChild(this._markerPane);

            this._popup = null;
            this._dragging = false;
            this._lastDragPos = null;

            this._bindEvents();
            this._redraw();
        }

        setView(center, zoom) {
            this.center = { lat: center[0], lon: center[1] };
            if (typeof zoom === 'number') {
                this.zoom = clamp(Math.round(zoom), MIN_ZOOM, MAX_ZOOM);
            }
            this._redraw();
            return this;
        }

        addLayer(layer) {
            if (layer instanceof LayerGroup) {
                layer.addTo(this);
            } else if (layer instanceof TileLayer) {
                layer.addTo(this);
            }
            return this;
        }

        fitBounds(bounds, options = {}) {
            if (!Array.isArray(bounds) || bounds.length === 0) return this;

            let minLat = Infinity, minLon = Infinity, maxLat = -Infinity, maxLon = -Infinity;
            bounds.forEach(pair => {
                const lat = pair[0];
                const lon = pair[1];
                if (lat < minLat) minLat = lat;
                if (lat > maxLat) maxLat = lat;
                if (lon < minLon) minLon = lon;
                if (lon > maxLon) maxLon = lon;
            });

            const padding = options.padding || [20, 20];
            const targetWidth = this.container.clientWidth - padding[0] * 2;
            const targetHeight = this.container.clientHeight - padding[1] * 2;

            let bestZoom = MIN_ZOOM;
            for (let z = MAX_ZOOM; z >= MIN_ZOOM; z--) {
                const nw = project(maxLat, minLon, z);
                const se = project(minLat, maxLon, z);
                const width = Math.abs(se.x - nw.x);
                const height = Math.abs(se.y - nw.y);
                if (width <= targetWidth && height <= targetHeight) {
                    bestZoom = z;
                    break;
                }
            }

            const centerLat = (minLat + maxLat) / 2;
            const centerLon = (minLon + maxLon) / 2;
            return this.setView([centerLat, centerLon], bestZoom);
        }

        _redraw() {
            this._redrawTiles();
            this._redrawMarkers();
        }

        _redrawTiles() {
            if (!this._tileLayer) return;
            const rect = this.container.getBoundingClientRect();
            const width = rect.width;
            const height = rect.height;

            const centerPoint = project(this.center.lat, this.center.lon, this.zoom);
            const startX = centerPoint.x - width / 2;
            const startY = centerPoint.y - height / 2;
            const endX = centerPoint.x + width / 2;
            const endY = centerPoint.y + height / 2;

            const startTileX = Math.floor(startX / TILE_SIZE);
            const startTileY = Math.floor(startY / TILE_SIZE);
            const endTileX = Math.floor(endX / TILE_SIZE);
            const endTileY = Math.floor(endY / TILE_SIZE);
            const tileCount = Math.pow(2, this.zoom);

            this._tilePane.innerHTML = '';

            for (let tileY = startTileY; tileY <= endTileY; tileY++) {
                if (tileY < 0 || tileY >= tileCount) continue;
                for (let tileX = startTileX; tileX <= endTileX; tileX++) {
                    let wrappedX = tileX;
                    while (wrappedX < 0) wrappedX += tileCount;
                    while (wrappedX >= tileCount) wrappedX -= tileCount;

                    const tile = document.createElement('img');
                    tile.className = 'simple-leaflet-tile';
                    tile.draggable = false;

                    const pixelX = tileX * TILE_SIZE - startX;
                    const pixelY = tileY * TILE_SIZE - startY;
                    tile.style.left = `${pixelX}px`;
                    tile.style.top = `${pixelY}px`;
                    tile.src = this._tileLayer.getTileUrl(wrappedX, tileY, this.zoom);
                    this._tilePane.appendChild(tile);
                }
            }
        }

        _redrawMarkers() {
            this._markerPane.innerHTML = '';
            if (!this._layerGroups.length) return;

            const rect = this.container.getBoundingClientRect();
            const width = rect.width;
            const height = rect.height;
            const centerPoint = project(this.center.lat, this.center.lon, this.zoom);

            this._layerGroups.forEach(group => {
                group._layers.forEach(marker => {
                    const point = project(marker.latlng.lat, marker.latlng.lon, this.zoom);
                    const px = point.x - centerPoint.x + width / 2;
                    const py = point.y - centerPoint.y + height / 2;

                    const markerElement = document.createElement('div');
                    markerElement.className = 'simple-leaflet-marker';
                    markerElement.style.left = `${px}px`;
                    markerElement.style.top = `${py}px`;

                    const icon = document.createElement('div');
                    icon.className = 'simple-leaflet-marker-icon';
                    markerElement.appendChild(icon);

                    if (marker.options.title) {
                        const label = document.createElement('div');
                        label.className = 'simple-leaflet-marker-label';
                        label.textContent = marker.options.title;
                        markerElement.appendChild(label);
                    }

                    if (marker.popupContent) {
                        markerElement.addEventListener('click', (event) => {
                            event.stopPropagation();
                            this._openPopup(marker, px, py);
                        });
                    }

                    this._markerPane.appendChild(markerElement);
                    marker._element = markerElement;
                });
            });
        }

        _openPopup(marker, x, y) {
            this._closePopup();

            const popup = document.createElement('div');
            popup.className = 'simple-leaflet-popup';
            popup.style.left = `${x}px`;
            popup.style.top = `${y - 12}px`;

            const closeBtn = document.createElement('button');
            closeBtn.className = 'simple-leaflet-popup-close';
            closeBtn.type = 'button';
            closeBtn.innerHTML = '&times;';
            closeBtn.addEventListener('click', () => this._closePopup());

            const content = document.createElement('div');
            if (typeof marker.popupContent === 'string') {
                content.innerHTML = marker.popupContent;
            } else if (marker.popupContent instanceof Node) {
                content.appendChild(marker.popupContent);
            }

            popup.appendChild(closeBtn);
            popup.appendChild(content);

            this._markerPane.appendChild(popup);
            this._popup = popup;
        }

        _closePopup() {
            if (this._popup && this._popup.parentNode) {
                this._popup.parentNode.removeChild(this._popup);
            }
            this._popup = null;
        }

        _bindEvents() {
            this.container.addEventListener('wheel', (event) => {
                event.preventDefault();
                const delta = event.deltaY > 0 ? -1 : 1;
                const newZoom = clamp(this.zoom + delta, MIN_ZOOM, MAX_ZOOM);
                if (newZoom !== this.zoom) {
                    this.zoom = newZoom;
                    this._redraw();
                }
            }, { passive: false });

            this.container.addEventListener('mousedown', (event) => {
                event.preventDefault();
                this._dragging = true;
                this._lastDragPos = { x: event.clientX, y: event.clientY };
                this.container.classList.add('dragging');
            });

            window.addEventListener('mousemove', (event) => {
                if (!this._dragging) return;
                const dx = event.clientX - this._lastDragPos.x;
                const dy = event.clientY - this._lastDragPos.y;
                this._lastDragPos = { x: event.clientX, y: event.clientY };

                const rect = this.container.getBoundingClientRect();
                const width = rect.width;
                const height = rect.height;
                const centerPoint = project(this.center.lat, this.center.lon, this.zoom);
                const newPoint = {
                    x: centerPoint.x - dx,
                    y: centerPoint.y - dy
                };
                const newCenter = unproject(newPoint.x, newPoint.y, this.zoom);
                this.center = newCenter;
                this._redraw();
            });

            window.addEventListener('mouseup', () => {
                if (this._dragging) {
                    this._dragging = false;
                    this.container.classList.remove('dragging');
                }
            });

            window.addEventListener('resize', () => this._redraw());
            this.container.addEventListener('click', () => this._closePopup());
        }
    }

    const L = {
        map(elementId, options) {
            return new MapInstance(elementId, options);
        },
        tileLayer(urlTemplate, options) {
            return new TileLayer(urlTemplate, options);
        },
        layerGroup() {
            return new LayerGroup();
        },
        marker(latlng, options) {
            return new Marker(latlng, options);
        }
    };

    global.L = L;
})(window);
