document.getElementById("saveChanges").addEventListener("click", () => {
    const select = document.getElementById("pageSelect");
    const pageSlug = select.options[select.selectedIndex].text.trim();

    const sections = {};
    document.querySelectorAll(".drop-zone").forEach((zone) => {
        const sectionName = zone.dataset.section;
        const modules = Array.from(zone.querySelectorAll(".module-item")).map((item) => item.dataset.name);
        sections[sectionName] = modules;
    });

    const result = { slug: pageSlug, sections };

    fetch("/admin/constructor", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(result),
    })
        .then((res) => {
            if (!res.ok) return res.text().then((text) => Promise.reject({ status: res.status, text }));
            return res.json();
        })
        .then((data) => {
            alert("Зміни успішно збережено.");
        })
        .catch((err) => {
            console.error("Помилка:", err);
            alert("Не вдалося зберегти зміни.");
        });
});

const moduleCache = {};
let updateTimeout;
let draggedElement = null;
let draggedFromZone = null;
let globalInstanceCounter = 0;

document.addEventListener("DOMContentLoaded", () => {
    const dropZones = document.querySelectorAll(".drop-zone");
    const deleteIndicator = document.getElementById("deleteIndicator");
    const pageSelect = document.getElementById("pageSelect");

    function capitalize(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function updatePreview() {
        ["header", "body", "footer"].forEach((section) => {
            const container = document.getElementById(`preview${capitalize(section)}`);
            const items = Array.from(document.querySelectorAll(`.drop-zone[data-section="${section}"] .module-item`));
            container.innerHTML = "";

            const promises = items.map((item) => {
                const name = item.dataset.name;
                const id = item.dataset.instanceId;
                const wrap = document.createElement("div");
                wrap.className = "preview-module";
                wrap.dataset.instanceId = id;
                wrap.dataset.name = name;

                if (moduleCache[id]) {
                    wrap.innerHTML = moduleCache[id];
                    return Promise.resolve(wrap);
                } else {
                    return fetch(`/admin/renderModule?name=${encodeURIComponent(name)}`, {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    })
                        .then((r) => (r.ok ? r.text() : Promise.reject()))
                        .then((html) => {
                            moduleCache[id] = html;
                            wrap.innerHTML = html;
                            return wrap;
                        })
                        .catch(() => {
                            wrap.innerHTML = `<div class="text-danger">⚠ Помилка модуля: ${name}</div>`;
                            return wrap;
                        });
                }
            });

            Promise.all(promises).then((wraps) => {
                wraps.forEach((wrap) => container.appendChild(wrap));
            });
        });
    }

    function schedulePreviewUpdate() {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(updatePreview, 200);
    }

    function addControlButtons(item) {
        const ctrl = document.createElement("div");
        ctrl.className = "module-controls";
        ctrl.innerHTML = `
            <button class="move-up btn btn-sm btn-secondary">↑</button>
            <button class="move-down btn btn-sm btn-secondary">↓</button>
            <button class="delete-module btn btn-sm btn-danger">×</button>`;
        ctrl.querySelector(".move-up").addEventListener("click", () => moveItem(item, -1));
        ctrl.querySelector(".move-down").addEventListener("click", () => moveItem(item, 1));
        ctrl.querySelector(".delete-module").addEventListener("click", () => {
            const id = item.dataset.instanceId;
            delete moduleCache[id];
            item.remove();
            schedulePreviewUpdate();
        });
        item.appendChild(ctrl);
    }

    function moveItem(item, dir) {
        const p = item.parentNode;
        const sib = Array.from(p.children);
        const i = sib.indexOf(item);
        const ni = i + dir;
        if (ni >= 0 && ni < sib.length) {
            dir < 0 ? p.insertBefore(item, sib[ni]) : p.insertBefore(sib[ni], item);
            schedulePreviewUpdate();
        }
    }

    // Drag & Drop setup
    document.addEventListener("dragstart", (e) => {
        if (e.target.matches(".component-item, .module-item")) {
            draggedElement = e.target;
            draggedFromZone = e.target.closest(".drop-zone");
            e.target.classList.add("dragging");
            deleteIndicator.classList.add("active");
        }
    });
    document.addEventListener("dragend", () => {
        document.querySelectorAll(".dragging").forEach((el) => el.classList.remove("dragging"));
        draggedElement = draggedFromZone = null;
        deleteIndicator.classList.remove("active");
    });
    dropZones.forEach((zone) => {
        zone.addEventListener("dragover", (e) => e.preventDefault());
        zone.addEventListener("drop", (e) => {
            e.preventDefault();
            if (!draggedElement) return;
            const clone = draggedElement.cloneNode(true);
            const id = `mod-${globalInstanceCounter++}`;
            clone.dataset.instanceId = id;
            if (!draggedElement.classList.contains("module-item")) {
                clone.classList.replace("component-item", "module-item");
                addControlButtons(clone);
            } else {
                draggedElement.remove();
            }
            zone.appendChild(clone);
            schedulePreviewUpdate();
        });
    });
    document.body.addEventListener("dragover", (e) => e.preventDefault());
    document.body.addEventListener("drop", (e) => {
        e.preventDefault();
        if (draggedElement && draggedFromZone && !e.target.closest(".drop-zone")) {
            const id = draggedElement.dataset.instanceId;
            delete moduleCache[id];
            draggedFromZone.removeChild(draggedElement);
            schedulePreviewUpdate();
        }
        deleteIndicator.classList.remove("active");
    });

    // Ініціалізація наявних
    document.querySelectorAll(".module-item").forEach((item) => {
        const id = `mod-${globalInstanceCounter++}`;
        item.dataset.instanceId = id;
        moduleCache[id] = null;
        addControlButtons(item);
    });

    // --- Додаємо перемикання сторінки ---
    pageSelect.addEventListener("change", () => {
        const slug = pageSelect.options[pageSelect.selectedIndex].text.trim();
        // Очистимо кеш і зони
        Object.keys(moduleCache).forEach((k) => delete moduleCache[k]);
        dropZones.forEach((z) => (z.innerHTML = ""));

        fetch(`/admin/constructor?slug=${encodeURIComponent(slug)}&ajax=1`, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((res) => (res.ok ? res.json() : Promise.reject()))
            .then((json) => {
                // json.sections = { header: [...], body: [...], footer: [...] }
                Object.entries(json.sections).forEach(([section, mods]) => {
                    const zone = document.querySelector(`.drop-zone[data-section="${section}"]`);
                    mods.forEach((name) => {
                        const div = document.createElement("div");
                        div.className = "list-group-item module-item";
                        div.draggable = true;
                        div.dataset.name = name;
                        const span = document.createElement("span");
                        span.className = "module-name";
                        span.textContent = name;
                        div.appendChild(span);
                        const id = `mod-${globalInstanceCounter++}`;
                        div.dataset.instanceId = id;
                        moduleCache[id] = null;
                        addControlButtons(div);
                        zone.appendChild(div);
                    });
                });
                updatePreview();
            })
            .catch((err) => {
                console.error("Не вдалося завантажити дані сторінки:", err);
                alert("Помилка при завантаженні розділів для сторінки.");
            });
    });

    // Перший рендер передперегляду
    updatePreview();
});
