function attachClickHandlers() {
    document.querySelectorAll(".room").forEach((room) => {
        room.addEventListener("mouseenter", () => {
            const id = room.getAttribute("data-id");
        });

        room.addEventListener("click", () => {
            document.querySelectorAll(".room.selected").forEach((r) => {
                r.classList.remove("selected");
            });

            room.classList.add("selected");
            const roomId = room.getAttribute("data-id");

            apiFetch(`/admin/getRoom?room_id=${encodeURIComponent(roomId)}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((data) => {
                    if (!Array.isArray(data.roomInfo) || data.roomInfo.length === 0) {
                        console.warn("Порожня відповідь або не масив:", data);
                        return;
                    }
                    renderRoomCard(data.roomInfo[0]);
                })
                .catch((err) => {
                    console.error("Error loading room data:", err);
                });
        });
    });
}

function renderRoomCard(data) {
    const cardContainer = document.getElementById("room-info-card");
    if (!cardContainer) return;

    const room = data.room;
    const block = data.block;
    const residents = data.residents;
    const warden = data.warden;
    const roomTypes = data.room_type || [];

    const residentCards = Array.isArray(residents)
        ? residents
              .map(
                  (user) => `
        <div class="resident-entry d-flex align-items-center mb-2 justify-content-between" data-id="${user.id}">
            <input type="hidden" name="residents[]" value="${user.id}">
            <div class="d-flex align-items-center">
                <img src="/public/users/${user.avatar ?? "default.png"}" class="rounded-circle me-2" alt="avatar" width="40" height="40">
                <span>${user.lastname} ${user.name} ${user.patronymic ?? ""}</span>
            </div>
            <button class="btn btn-sm btn-danger remove-resident-btn">×</button>
        </div>
    `
              )
              .join("")
        : "<p>Немає мешканців</p>";

    const wardenCard = warden
        ? `
        <div class="d-flex align-items-center">
            <img src="/public/users/${warden.avatar ?? "default.png"}" class="rounded-circle me-2" alt="avatar" width="40" height="40">
            <div>
                <div>${warden.lastname} ${warden.name} ${warden.patronymic ?? ""}</div>
                <div class="text-muted small">${warden.email}</div>
            </div>
        </div>`
        : "<p>Інформація про старосту відсутня</p>";

    // Створюємо селект для типу кімнати
    const roomTypeOptions = roomTypes
        .map(
            (type) => `<option value="${type}" ${type === room.room_type ? "selected" : ""}>${type.charAt(0).toUpperCase() + type.slice(1)}</option>`
        )
        .join("");

    cardContainer.innerHTML = `
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                Кімната №${room.name} (${room.room_type})
                <span class="badge bg-secondary">ID: ${room.id}</span>
            </div>
            <div class="card-body">
                <form id="update-room-form" enctype="multipart/form-data">
                    <input type="hidden" name="room_id" value="${room.id}">
                    <div class="mb-3">
                        <label class="form-label">Кількість місць</label>
                        <input type="number" class="form-control" name="places" value="${room.places}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Тип кімнати</label>
                        <select class="form-select" name="room_type" required>
                            ${roomTypeOptions}
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Фото кімнати</label><br>
                        <img src="/public/rooms/${
                            room.img ?? "default.png"
                        }" alt="room image" class="img-fluid rounded mb-2" style="max-width: 200px;">
                        <input type="file" name="image" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary">Оновити кімнату</button>
                </form>

                <hr>
                <h6>Мешканці:</h6>
                <div id="resident-list">
                    ${residentCards}
                </div>

                <div class="input-group mt-3">
                    <input type="text" id="resident-search" class="form-control" placeholder="Пошук студента...">
                    <button class="btn btn-outline-secondary" id="search-residents-btn">Пошук</button>
                </div>
                <div id="resident-search-results" class="mt-2"></div>

                <hr>
                <h6>Староста блоку:</h6>
                ${wardenCard}
            </div>
        </div>
    `;

    setupRoomUpdateForm();
    setupResidentSearch(room.id);
    setupResidentRemoval();
}

function setupRoomUpdateForm() {
    const form = document.getElementById("update-room-form");
    if (!form) return;

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        const residentInputs = document.querySelectorAll("#resident-list input[name='residents[]']");
        residentInputs.forEach((input) => {
            formData.append("residents[]", input.value);
        });

        apiFetch("/admin/updateRoom", {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        }).then((response) => {
            if (response.success) {
                alert("Кімната оновлена успішно!");
                const roomId = formData.get("room_id");
                return apiFetch(`/admin/getRoom?room_id=${encodeURIComponent(roomId)}`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
            } else {
                // Виводимо помилку через showError та кидаємо, щоб потрапити в catch
                showError(response.message || "Не вдалося оновити кімнату.");
                throw new Error(response.message || "Не вдалося оновити кімнату.");
            }
        });
    });
}

function setupResidentSearch(roomId) {
    const searchInput = document.getElementById("resident-search");
    const resultContainer = document.getElementById("resident-search-results");
    const selectedContainer = document.getElementById("resident-list");

    if (!searchInput || !resultContainer || !selectedContainer) return;

    let timeout;
    searchInput.addEventListener("input", () => {
        const query = searchInput.value.trim();
        clearTimeout(timeout);

        if (query.length < 2) {
            resultContainer.innerHTML = "";
            return;
        }

        timeout = setTimeout(() => {
            apiFetch(`/admin/searchUsers?query=${encodeURIComponent(query)}&room_id=${roomId}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((users) => {
                    if (!Array.isArray(users) || users.length === 0 || !Array.isArray(users[0])) {
                        resultContainer.innerHTML = "<p class='text-muted'>Нічого не знайдено</p>";
                        return;
                    }

                    resultContainer.innerHTML = users[0]
                        .map(
                            (u) => `
                        <div class="search-result-item d-flex align-items-center justify-content-between border rounded p-2 mb-2" data-id="${u.id}">
                            <div class="d-flex align-items-center">
                                <img src="/public/users/${u.avatar ?? "default.png"}" width="40" height="40" class="rounded-circle me-2" alt="avatar">
                                <div>
                                    <div><strong>${u.lastname} ${u.name} ${u.patronymic ?? ""}</strong></div>
                                    <div class="text-muted small">${u.email}</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success ms-3">Додати</button>
                        </div>
                    `
                        )
                        .join("");

                    resultContainer.querySelectorAll(".search-result-item button").forEach((button) => {
                        button.addEventListener("click", () => {
                            const item = button.closest(".search-result-item");
                            const id = item.dataset.id;

                            if (selectedContainer.querySelector(`[data-id="${id}"]`)) return;

                            const avatar = item.querySelector("img").src;
                            const name = item.querySelector("strong").textContent;
                            const email = item.querySelector(".text-muted").textContent;

                            const userDiv = document.createElement("div");
                            userDiv.classList.add("resident-entry", "d-flex", "align-items-center", "justify-content-between", "mb-2");
                            userDiv.dataset.id = id;

                            userDiv.innerHTML = `
                            <input type="hidden" name="residents[]" value="${id}">
                            <div class="d-flex align-items-center">
                                <img src="${avatar}" class="rounded-circle me-2" width="40" height="40" alt="avatar">
                                <div>
                                    <div><strong>${name}</strong></div>
                                    <div class="text-muted small">${email}</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-resident-btn ms-3">×</button>
                        `;

                            selectedContainer.appendChild(userDiv);
                            item.remove();
                        });
                    });
                })
                .catch((err) => {
                    console.error("Error searching users:", err);
                    resultContainer.innerHTML = "<p class='text-danger'>Помилка під час пошуку користувачів.</p>";
                });
        }, 300);
    });
}

function setupResidentRemoval() {
    const selectedContainer = document.getElementById("resident-list");
    if (!selectedContainer) return;

    selectedContainer.addEventListener("click", (e) => {
        if (e.target.classList.contains("remove-resident-btn")) {
            const entry = e.target.closest(".resident-entry");
            if (entry) entry.remove();
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    function loadFloorSvg() {
        const floorInput = document.querySelector('input[name="floor"]:checked');
        if (!floorInput) {
            console.error("Floor input not found");
            return;
        }

        const floor = floorInput.value;
        apiFetchText(`/admin/getFloor?floor=${encodeURIComponent(floor)}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((svgHtml) => {
                const container = document.getElementById("map-container");
                if (container) {
                    container.innerHTML = ""; // очистити контейнер
                    container.innerHTML = svgHtml; // вставити SVG
                    attachClickHandlers();
                } else {
                    console.error("#map-container not found");
                }
            })
            .catch((error) => {
                console.error("Error loading floor SVG:", error);
            });
    }

    loadFloorSvg();

    document.querySelectorAll('input[name="floor"]').forEach((radio) => {
        radio.addEventListener("change", () => {
            loadFloorSvg();
        });
    });
});
