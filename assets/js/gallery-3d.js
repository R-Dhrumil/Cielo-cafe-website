/* 3D Infinite Gallery – vanilla Three.js port (no React / build step) */
(function () {
    'use strict';

    function initGallery3D() {
        var root = document.getElementById('gallery3d');
        if (!root) return;

        var rawConfig = root.getAttribute('data-config');
        if (!rawConfig) return;

        var cfg = {};
        try {
            cfg = JSON.parse(rawConfig);
        } catch (err) {
            console.error('Invalid gallery3d data-config JSON', err);
            return;
        }

        var images = cfg.images || [];
        var visibleCount = cfg.visibleCount || 8;
        var speed = cfg.speed || 1;
        var depthRange = cfg.depthRange || 50;
        var fade = cfg.fadeSettings || {
            fadeIn: { start: 0.05, end: 0.25 },
            fadeOut: { start: 0.75, end: 0.95 }
        };
        var blur = cfg.blurSettings || {
            maxBlur: 3.0,
            blurIn: { start: 0.0, end: 0.15 },
            blurOut: { start: 0.85, end: 1.0 }
        };
        var totalImages = images.length;

        if (!totalImages) return;

        // ---- WebGL check -> fallback grid --------------------------------------
        var hasWebGL = false;
        try {
            var testCanvas = document.createElement('canvas');
            hasWebGL = !!(testCanvas.getContext('webgl') || testCanvas.getContext('experimental-webgl'));
        } catch (e) {}
        if (!hasWebGL || typeof THREE === 'undefined') {
            root.classList.add('is-fallback');
            return;
        }

        // ---- Renderer / camera -------------------------------------------------
        var container = document.getElementById('gallery3dCanvas');
        if (!container) return;

        // Clear any previous canvas
        container.innerHTML = '';

        var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        container.appendChild(renderer.domElement);
        var canvas = renderer.domElement;

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(55, 1, 0.1, 1000);
        camera.position.set(0, 0, 0);

        function resize() {
            var w = container.clientWidth || window.innerWidth || 1;
            var h = container.clientHeight || window.innerHeight || 1;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            camera.updateProjectionMatrix();
        }
        window.addEventListener('resize', resize);
        resize();

        // ---- Shader material (cloth curve + flag wave + blur) -------------------
        var vertexShader = [
            'uniform float scrollForce;',
            'uniform float time;',
            'uniform float isHovered;',
            'varying vec2 vUv;',
            'void main() {',
            '  vUv = uv;',
            '  vec3 pos = position;',
            '  float curveIntensity = scrollForce * 0.3;',
            '  float distanceFromCenter = length(pos.xy);',
            '  float curve = distanceFromCenter * distanceFromCenter * curveIntensity;',
            '  float ripple1 = sin(pos.x * 2.0 + scrollForce * 3.0) * 0.02;',
            '  float ripple2 = sin(pos.y * 2.5 + scrollForce * 2.0) * 0.015;',
            '  float clothEffect = (ripple1 + ripple2) * abs(curveIntensity) * 2.0;',
            '  float flagWave = 0.0;',
            '  if (isHovered > 0.5) {',
            '    float wavePhase = pos.x * 3.0 + time * 8.0;',
            '    float waveAmplitude = sin(wavePhase) * 0.1;',
            '    float dampening = smoothstep(-0.5, 0.5, pos.x);',
            '    flagWave = waveAmplitude * dampening;',
            '    flagWave += sin(pos.x * 5.0 + time * 12.0) * 0.03 * dampening;',
            '  }',
            '  pos.z -= (curve + clothEffect + flagWave);',
            '  gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);',
            '}'
        ].join('\n');

        var fragmentShader = [
            'uniform sampler2D map;',
            'uniform float opacity;',
            'uniform float blurAmount;',
            'uniform float scrollForce;',
            'uniform vec2 texelSize;',
            'varying vec2 vUv;',
            'void main() {',
            '  vec4 color = texture2D(map, vUv);',
            '  if (blurAmount > 0.0) {',
            '    vec4 blurred = vec4(0.0);',
            '    float total = 0.0;',
            '    for (float x = -2.0; x <= 2.0; x += 1.0) {',
            '      for (float y = -2.0; y <= 2.0; y += 1.0) {',
            '        vec2 offset = vec2(x, y) * texelSize * blurAmount;',
            '        float weight = 1.0 / (1.0 + length(vec2(x, y)));',
            '        blurred += texture2D(map, vUv + offset) * weight;',
            '        total += weight;',
            '      }',
            '    }',
            '    color = blurred / total;',
            '  }',
            '  float curveHighlight = abs(scrollForce) * 0.05;',
            '  color.rgb += vec3(curveHighlight * 0.1);',
            '  gl_FragColor = vec4(color.rgb, color.a * opacity);',
            '}'
        ].join('\n');

        function createMaterial() {
            return new THREE.ShaderMaterial({
                transparent: true,
                uniforms: {
                    map: { value: null },
                    opacity: { value: 1.0 },
                    blurAmount: { value: 0.0 },
                    scrollForce: { value: 0.0 },
                    time: { value: 0.0 },
                    isHovered: { value: 0.0 },
                    texelSize: { value: new THREE.Vector2(1, 1) }
                },
                vertexShader: vertexShader,
                fragmentShader: fragmentShader
            });
        }

        // ---- Load textures -------------------------------------------------------
        var textures = new Array(totalImages).fill(null);
        var loader = new THREE.TextureLoader();
        var started = false;

        images.forEach(function (img, i) {
            loader.load(
                img.src,
                function (tex) {
                    tex.minFilter = THREE.LinearFilter;
                    textures[i] = tex;
                    if (!started) { started = true; requestAnimationFrame(tick); }
                },
                undefined,
                function () { /* skip images that fail to load */ }
            );
        });

        // ---- Spatial layout (same golden-angle distribution as the original) -----
        var MAX_H = 8, MAX_V = 8;
        var geometry = new THREE.PlaneGeometry(1, 1, 32, 32);
        var planes = [];

        for (var i = 0; i < visibleCount; i++) {
            var hAngle = (i * 2.618) % (Math.PI * 2);
            var vAngle = (i * 1.618 + Math.PI / 3) % (Math.PI * 2);
            var hRadius = (i % 3) * 1.2;
            var vRadius = ((i + 1) % 4) * 0.8;

            var material = createMaterial();
            var mesh = new THREE.Mesh(geometry, material);
            mesh.visible = false;
            scene.add(mesh);

            planes.push({
                z: ((depthRange / visibleCount) * i) % depthRange,
                imageIndex: i % totalImages,
                x: (Math.sin(hAngle) * hRadius * MAX_H) / 3,
                y: (Math.cos(vAngle) * vRadius * MAX_V) / 4,
                shown: -1,
                mesh: mesh,
                material: material
            });
        }

        // ---- Interaction ---------------------------------------------------------
        var velocity = 0;
        var autoPlay = true;
        var lastInteraction = Date.now();

        function nudge(amount) {
            velocity += amount;
            autoPlay = false;
            lastInteraction = Date.now();
        }

        canvas.addEventListener('wheel', function (e) {
            e.preventDefault();
            nudge(e.deltaY * 0.01 * speed);
        }, { passive: false });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') nudge(-2 * speed);
            else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') nudge(2 * speed);
        });

        // Mobile "Tap to Interact" Controls Integration
        var unlockBtn = document.getElementById('galleryUnlockBtn');
        var activeBar = document.getElementById('galleryActiveBar');
        var doneBtn = document.getElementById('galleryDoneBtn');
        var navPrev = document.getElementById('galleryNavPrev');
        var navNext = document.getElementById('galleryNavNext');

        function setInteractive(active) {
            if (active) {
                root.classList.add('is-interactive');
                if (unlockBtn) unlockBtn.style.display = 'none';
                if (activeBar) activeBar.style.display = 'inline-flex';
                nudge(1.2 * speed);
            } else {
                root.classList.remove('is-interactive');
                if (activeBar) activeBar.style.display = 'none';
                if (unlockBtn) unlockBtn.style.display = 'inline-flex';
            }
        }

        if (unlockBtn) {
            unlockBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                setInteractive(true);
            });
        }

        if (doneBtn) {
            doneBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                setInteractive(false);
            });
        }

        if (navPrev) {
            navPrev.addEventListener('click', function (e) {
                e.stopPropagation();
                nudge(-3.5 * speed);
            });
        }

        if (navNext) {
            navNext.addEventListener('click', function (e) {
                e.stopPropagation();
                nudge(3.5 * speed);
            });
        }

        var touchY = null;
        var touchX = null;
        canvas.addEventListener('touchstart', function (e) {
            if (!root.classList.contains('is-interactive')) return;
            touchY = e.touches[0].clientY;
            touchX = e.touches[0].clientX;
        }, { passive: true });

        canvas.addEventListener('touchmove', function (e) {
            if (!root.classList.contains('is-interactive') || touchY === null || touchX === null) return;
            var x = e.touches[0].clientX;
            var y = e.touches[0].clientY;
            var dx = touchX - x;
            var dy = touchY - y;

            if (e.cancelable) e.preventDefault();
            var primaryDelta = Math.abs(dx) > Math.abs(dy) ? dx : dy;
            nudge(primaryDelta * 0.05 * speed);
            touchX = x;
            touchY = y;
        }, { passive: false });

        canvas.addEventListener('touchend', function () {
            touchY = null;
            touchX = null;
        });

        setInterval(function () {
            var idleTime = Date.now() - lastInteraction;
            if (idleTime > 3000) autoPlay = true;
            // Auto-revert to free scroll mode after 8 seconds of idle in interactive mode
            if (idleTime > 8000 && root.classList.contains('is-interactive')) {
                setInteractive(false);
            }
        }, 1000);

        // Hover (flag-wave) via raycasting
        var raycaster = new THREE.Raycaster();
        var pointer = new THREE.Vector2(2, 2);
        var hovered = null;

        canvas.addEventListener('pointermove', function (e) {
            var r = canvas.getBoundingClientRect();
            pointer.x = ((e.clientX - r.left) / r.width) * 2 - 1;
            pointer.y = -((e.clientY - r.top) / r.height) * 2 + 1;
        });
        canvas.addEventListener('pointerleave', function () { pointer.set(2, 2); });

        function updateHover() {
            raycaster.setFromCamera(pointer, camera);
            var meshes = planes
                .filter(function (p) { return p.mesh.visible && p.material.uniforms.opacity.value > 0.05; })
                .map(function (p) { return p.mesh; });
            var hits = pointer.x > 1 ? [] : raycaster.intersectObjects(meshes);
            hovered = hits.length ? hits[0].object : null;
            planes.forEach(function (p) {
                p.material.uniforms.isHovered.value = p.mesh === hovered ? 1.0 : 0.0;
            });
        }

        // ---- Fade / blur helpers (port of the original range logic) --------------
        function clamp01(v) { return Math.max(0, Math.min(1, v)); }

        function calcOpacity(n) {
            var o = 1;
            if (n >= fade.fadeIn.start && n <= fade.fadeIn.end) {
                o = (n - fade.fadeIn.start) / (fade.fadeIn.end - fade.fadeIn.start);
            } else if (n < fade.fadeIn.start) {
                o = 0;
            } else if (n >= fade.fadeOut.start && n <= fade.fadeOut.end) {
                o = 1 - (n - fade.fadeOut.start) / (fade.fadeOut.end - fade.fadeOut.start);
            } else if (n > fade.fadeOut.end) {
                o = 0;
            }
            return clamp01(o);
        }

        function calcBlur(n) {
            var b = 0, m = blur.maxBlur;
            if (n >= blur.blurIn.start && n <= blur.blurIn.end) {
                b = m * (1 - (n - blur.blurIn.start) / (blur.blurIn.end - blur.blurIn.start));
            } else if (n < blur.blurIn.start) {
                b = m;
            } else if (n >= blur.blurOut.start && n <= blur.blurOut.end) {
                b = m * ((n - blur.blurOut.start) / (blur.blurOut.end - blur.blurOut.start));
            } else if (n > blur.blurOut.end) {
                b = m;
            }
            return Math.max(0, Math.min(m, b));
        }

        // ---- Per-plane texture / aspect update -----------------------------------
        function applyTexture(p) {
            var tex = textures[p.imageIndex];
            if (!tex) { p.mesh.visible = false; return; }
            if (p.shown !== p.imageIndex) {
                p.material.uniforms.map.value = tex;
                var w = tex.image.width || 1, h = tex.image.height || 1;
                p.material.uniforms.texelSize.value.set(1 / w, 1 / h);
                var aspect = w / h;
                if (aspect > 1) p.mesh.scale.set(2 * aspect, 2, 1);
                else p.mesh.scale.set(2, 2 / aspect, 1);
                p.shown = p.imageIndex;
            }
            p.mesh.visible = true;
        }

        // ---- Main loop -----------------------------------------------------------
        var clock = new THREE.Clock();
        var imageAdvance = visibleCount % totalImages || totalImages;

        function tick() {
            requestAnimationFrame(tick);

            var delta = Math.min(clock.getDelta(), 0.1);
            var time = clock.elapsedTime;

            if (autoPlay) velocity += 0.3 * delta;
            velocity *= 0.95;

            planes.forEach(function (p) {
                var newZ = p.z + velocity * delta * 10;
                var wrapsF = 0, wrapsB = 0;

                if (newZ >= depthRange) {
                    wrapsF = Math.floor(newZ / depthRange);
                    newZ -= depthRange * wrapsF;
                } else if (newZ < 0) {
                    wrapsB = Math.ceil(-newZ / depthRange);
                    newZ += depthRange * wrapsB;
                }

                if (wrapsF > 0) {
                    p.imageIndex = (p.imageIndex + wrapsF * imageAdvance) % totalImages;
                }
                if (wrapsB > 0) {
                    var step = p.imageIndex - wrapsB * imageAdvance;
                    p.imageIndex = ((step % totalImages) + totalImages) % totalImages;
                }

                p.z = ((newZ % depthRange) + depthRange) % depthRange;

                applyTexture(p);
                p.mesh.position.set(p.x, p.y, p.z - depthRange / 2);

                var n = p.z / depthRange;
                var u = p.material.uniforms;
                u.time.value = time;
                u.scrollForce.value = velocity;
                u.opacity.value = calcOpacity(n);
                u.blurAmount.value = calcBlur(n);
            });

            updateHover();
            renderer.render(scene, camera);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGallery3D);
    } else {
        initGallery3D();
    }
})();
