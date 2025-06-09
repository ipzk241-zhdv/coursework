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

            apiFetch(`/site/getRoom?room_id=${encodeURIComponent(roomId)}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((data) => {
                    if (!Array.isArray(data) || data.length === 0) {
                        console.warn("Порожня відповідь або не масив:", data);
                        return;
                    }
                    renderRoomCard(data[0]);
                })
                .catch((err) => console.error("Error loading room data:", err));
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

    const residentCards = Array.isArray(residents)
        ? residents
              .map(
                  (user) => `
            <div class="d-flex align-items-center mb-2">
                <img src="/public/users/${user.avatar ?? "default.png"}" class="rounded-circle me-2" alt="avatar" width="40" height="40">
                <span>${user.lastname} ${user.name} ${user.patronymic ?? ""}</span>
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

    cardContainer.innerHTML = `
        <div class="card mt-4">
            <div class="card-header">
                Кімната №${room.name} (${room.room_type})
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Блок:</strong> ${block.name}<br>
                    <strong>Кількість місць:</strong> ${room.places}
                </div>
                <img src="/public/rooms/${room.img ?? "default.png"}" alt="room image" class="img-fluid rounded mb-3">
                ${
                    room.places > 0
                        ? `
                    <h6>Мешканці:</h6>
                    ${residentCards}
                `
                        : ""
                }
                <hr>
                <h6>Староста блоку:</h6>
                ${wardenCard}
            </div>
        </div>
    `;
}

document.addEventListener("DOMContentLoaded", () => {
    function loadFloorSvg() {
        const floorInput = document.querySelector('input[name="floor"]:checked');
        if (!floorInput) {
            console.error("Floor input not found");
            return;
        }

        const floor = floorInput.value;
        apiFetchText(`/site/getFloor?floor=${encodeURIComponent(floor)}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((svgHtml) => {
                const container = document.getElementById("map-container");
                if (container) {
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
