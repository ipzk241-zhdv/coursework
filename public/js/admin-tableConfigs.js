function loadConfigs(tableName = "") {
    let url = "/admin/tableConfigs?ajax=1";
    if (tableName) {
        url += "&table_name=" + encodeURIComponent(tableName);
    }

    fetch(url, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((r) => r.json())
        .then((data) => {
            const tbody = document.querySelector("#configsTable tbody");
            tbody.innerHTML = "";
            data.forEach((cfg) => {
                const row = document.createElement("tr");
                row.innerHTML = `
                <td>${cfg.id}</td>
                <td>${cfg.table_name}</td>
                <td>${cfg.field}</td>
                <td>${cfg.label}</td>
                <td>${cfg.type}</td>
                <td>${cfg.sortable ? "✔️" : ""}</td>
                <td>${cfg.searchable ? "✔️" : ""}</td>
                <td>${cfg.visible ? "✔️" : ""}</td>
                <td>${cfg.position}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editConfig(${cfg.id})">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteConfig(${cfg.id})">🗑️</button>
                </td>
            `;
                tbody.appendChild(row);
            });
        });
}

function editConfig(id) {
    fetch("/admin/tableConfigs?ajax=1&id=" + id, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((r) => r.json())
        .then((item) => {
            if (!item) return;

            const form = document.getElementById("configForm");
            form.querySelector("#config-id").value = item.id;
            form.querySelector("#table_name").value = item.table_name;
            form.querySelector("#field").value = item.field;
            form.querySelector("#label").value = item.label;
            form.querySelector("#type").value = item.type;
            form.querySelector("#sortable").checked = !!item.sortable;
            form.querySelector("#searchable").checked = !!item.searchable;
            form.querySelector("#visible").checked = !!item.visible;
            form.querySelector("#position").value = item.position;
        });
}

function deleteConfig(id) {
    if (!confirm("Ви впевнені, що хочете видалити цей запис?")) return;

    const formData = new FormData();
    formData.append("delete", "1");
    formData.append("id", id);

    fetch("/admin/tableConfigs", {
        method: "POST",
        body: formData,
    })
        .then((r) => r.json())
        .then((data) => {
            loadConfigs(data);
        });
}

function autoGenerate() {
    const tableSelect = document.getElementById("table_name");
    const table = tableSelect.value;
    if (!table) return alert("Вкажіть назву таблиці");

    fetch("/admin/autoGenerateConfigs?table=" + encodeURIComponent(table), {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((r) => r.json())
        .then((data) => {
            if (data.status === "ok") {
                // Встановлюємо вибір у select (можливо, не обов'язково, але надійніше)
                tableSelect.value = table;

                // Підвантажуємо конфіги саме для цієї таблиці
                loadConfigs(table);

                alert("Конфігурація таблиці оновлена");
            } else {
                alert("Помилка: " + data.message);
            }
        });
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("configForm");
    const tableSelect = document.getElementById("table_name");

    loadConfigs(tableSelect.value || "");

    tableSelect.addEventListener("change", () => {
        loadConfigs(tableSelect.value);
    });

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        ["sortable", "searchable", "visible"].forEach((field) => {
            if (!formData.has(field)) {
                formData.append(field, "0");
            }
        });

        const id = formData.get("id");
        formData.append(id ? "put" : "add", "1");

        fetch("/admin/tableConfigs", {
            method: "POST",
            body: formData,
        })
            .then((r) => r.json())
            .then((res) => {
                const selectedTable = formData.get("table_name");
                loadConfigs(selectedTable);
                form.reset();
                form.querySelector("#table_name").value = selectedTable; // зберігаємо вибране
                form.querySelector('input[name="put"]').value = "1";
            });
    });
});
