<style>
    .pagination-wrapper {
        overflow-x: auto;
        white-space: nowrap;
        padding: 0.5rem;
    }

    .pagination-wrapper ul.pagination {
        flex-wrap: nowrap;
    }
</style>

<div id="users-app" class="container my-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="search-input" class="form-control" placeholder="🔍 Пошук...">
        </div>
    </div>
    <div class="text-end my-2">
        <button id="add-btn" class="btn btn-primary" style="display: none;">➕ Додати запис</button>
    </div>
    <div class="text-end my-2">
        <button id="save-btn" class="btn btn-success" style="display: none;">💾 Зберегти зміни</button>
    </div>
    <div id="table-container" class="table-responsive">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Завантаження...</span>
            </div>
        </div>
    </div>
</div>

<script src="/public/js/ajax-error-handler.js"></script>
<script>
    const apiUrl = "<?= $apiUrl ?>";
    const configUrl = "<?= $configUrl ?>";

    let currentPage = 1;
    let sortField = "id";
    let sortDir = "asc";
    let searchQuery = "";
    let tableConfig = [];

    async function fetchTableConfig() {
        const response = await fetch(configUrl, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        const config = await response.json();

        tableConfig = config
            .filter((c) => c.visible == 1)
            .sort((a, b) => a.position - b.position)
            .map((c) => ({
                field: c.field,
                label: c.label || c.field,
                sortable: c.sortable == 1,
                searchable: c.searchable == 1,
                type: c.type || "text",
            }));

        sortField = tableConfig[0]?.field || "id";
        await loadData();
    }

    async function loadData() {
        const params = new URLSearchParams({
            page: currentPage,
            sort: sortField,
            dir: sortDir,
            search: searchQuery,
        });

        const response = await fetch(`${apiUrl}?${params.toString()}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        const json = await response.json();
        if (json.success) {
            renderTable(json.data);
            renderPagination(json.pagination);
        } else {
            document.getElementById("table-container").innerHTML = '<div class="alert alert-danger">Помилка завантаження даних.</div>';
        }
    }

    function renderTable(data) {
        const table = document.createElement("table");
        table.className = "table table-bordered table-hover table-striped align-middle";

        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");

        tableConfig.forEach((col) => {
            const th = document.createElement("th");
            th.textContent = col.label;
            th.classList.add("text-nowrap");

            if (col.sortable) {
                th.style.cursor = "pointer";
                th.addEventListener("click", () => {
                    if (sortField === col.field) {
                        sortDir = sortDir === "asc" ? "desc" : "asc";
                    } else {
                        sortField = col.field;
                        sortDir = "asc";
                    }
                    loadData();
                });
                th.innerHTML += ` <i class="bi bi-arrow-${sortField === col.field ? sortDir : "down"}"></i>`;
            }
            headerRow.appendChild(th);
        });

        const actionTh = document.createElement("th");
        actionTh.textContent = "Дії";
        actionTh.classList.add("text-nowrap");
        headerRow.appendChild(actionTh);
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement("tbody");

        const newRow = document.createElement("tr");
        newRow.classList.add("table-success");
        const newRecord = {};

        tableConfig.forEach((col) => {
            const td = document.createElement("td");
            const input = document.createElement("input");

            input.className = "form-control";
            input.placeholder = col.label;
            input.dataset.field = col.field;
            input.type = col.type === "date" ? "date" : "text";

            input.addEventListener("input", (e) => {
                newRecord[col.field] = e.target.value;
                document.getElementById("add-btn").style.display = "inline-block";
            });

            td.appendChild(input);
            newRow.appendChild(td);
        });

        tbody.appendChild(newRow);

        // Якщо немає даних — не рендерити решту рядків
        if (Array.isArray(data) && data.length > 0) {
            data.forEach((user) => {
                const row = document.createElement("tr");
                tableConfig.forEach((col) => {
                    const td = document.createElement("td");
                    if (col.type === "date") {
                        const input = document.createElement("input");
                        input.type = "date";
                        input.className = "form-control";
                        const rawValue = user[col.field];
                        input.value = rawValue ? rawValue.substring(0, 10) : "";
                        input.dataset.id = user.id;
                        input.dataset.field = col.field;
                        td.appendChild(input);
                    } else {
                        td.textContent = user[col.field];
                        td.contentEditable = true;
                        td.dataset.id = user.id;
                        td.dataset.field = col.field;
                    }

                    row.appendChild(td);
                });
                const actionTd = document.createElement("td");
                const deleteBtn = document.createElement("button");
                deleteBtn.className = "btn btn-sm btn-danger";
                deleteBtn.innerHTML = "🗑️";
                deleteBtn.addEventListener("click", async () => {
                    if (confirm("Ви впевнені, що хочете видалити цей запис?")) {
                        await fetch(apiUrl, {
                            method: "POST",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                id: user.id,
                                delete: true
                            }),
                        });

                        await loadData(); // перезавантажити таблицю
                    }
                });
                actionTd.appendChild(deleteBtn);
                row.appendChild(actionTd);
                tbody.appendChild(row);
            });
        }

        table.appendChild(tbody);
        const container = document.getElementById("table-container");
        container.innerHTML = "";
        container.appendChild(table);
    }

    function renderPagination({
        page,
        pages
    }) {
        const container = document.getElementById("table-container");
        const pagWrapper = document.createElement("div");
        pagWrapper.className = "pagination-wrapper";

        const pag = document.createElement("nav");
        const ul = document.createElement("ul");
        ul.className = "pagination justify-content-center mt-3";

        // Максимальна кількість відображуваних сторінок (непарне число)
        const maxVisiblePages = 7;
        let startPage, endPage;

        if (pages <= maxVisiblePages) {
            startPage = 1;
            endPage = pages;
        } else {
            const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
            const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;

            if (page <= maxPagesBeforeCurrent) {
                startPage = 1;
                endPage = maxVisiblePages;
            } else if (page + maxPagesAfterCurrent >= pages) {
                startPage = pages - maxVisiblePages + 1;
                endPage = pages;
            } else {
                startPage = page - maxPagesBeforeCurrent;
                endPage = page + maxPagesAfterCurrent;
            }
        }

        // Кнопка "Попередня"
        const prevLi = document.createElement("li");
        prevLi.className = "page-item" + (page === 1 ? " disabled" : "");
        const prevBtn = document.createElement("button");
        prevBtn.className = "page-link";
        prevBtn.innerHTML = "&laquo;";
        prevBtn.addEventListener("click", () => {
            if (page > 1) {
                currentPage = page - 1;
                loadData();
            }
        });
        prevLi.appendChild(prevBtn);
        ul.appendChild(prevLi);

        // Перша сторінка
        if (startPage > 1) {
            const firstLi = document.createElement("li");
            firstLi.className = "page-item";
            const firstBtn = document.createElement("button");
            firstBtn.className = "page-link";
            firstBtn.textContent = "1";
            firstBtn.addEventListener("click", () => {
                currentPage = 1;
                loadData();
            });
            firstLi.appendChild(firstBtn);
            ul.appendChild(firstLi);

            if (startPage > 2) {
                const dotsLi = document.createElement("li");
                dotsLi.className = "page-item disabled";
                const dotsSpan = document.createElement("span");
                dotsSpan.className = "page-link";
                dotsSpan.textContent = "...";
                dotsLi.appendChild(dotsSpan);
                ul.appendChild(dotsLi);
            }
        }

        // Основні сторінки
        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement("li");
            li.className = "page-item" + (i === page ? " active" : "");
            const btn = document.createElement("button");
            btn.className = "page-link";
            btn.textContent = i;
            btn.addEventListener("click", () => {
                currentPage = i;
                loadData();
            });
            li.appendChild(btn);
            ul.appendChild(li);
        }

        // Остання сторінка
        if (endPage < pages) {
            if (endPage < pages - 1) {
                const dotsLi = document.createElement("li");
                dotsLi.className = "page-item disabled";
                const dotsSpan = document.createElement("span");
                dotsSpan.className = "page-link";
                dotsSpan.textContent = "...";
                dotsLi.appendChild(dotsSpan);
                ul.appendChild(dotsLi);
            }

            const lastLi = document.createElement("li");
            lastLi.className = "page-item";
            const lastBtn = document.createElement("button");
            lastBtn.className = "page-link";
            lastBtn.textContent = pages;
            lastBtn.addEventListener("click", () => {
                currentPage = pages;
                loadData();
            });
            lastLi.appendChild(lastBtn);
            ul.appendChild(lastLi);
        }

        // Кнопка "Наступна"
        const nextLi = document.createElement("li");
        nextLi.className = "page-item" + (page === pages ? " disabled" : "");
        const nextBtn = document.createElement("button");
        nextBtn.className = "page-link";
        nextBtn.innerHTML = "&raquo;";
        nextBtn.addEventListener("click", () => {
            if (page < pages) {
                currentPage = page + 1;
                loadData();
            }
        });
        nextLi.appendChild(nextBtn);
        ul.appendChild(nextLi);

        pag.appendChild(ul);
        pagWrapper.appendChild(pag);
        container.appendChild(pagWrapper);
    }

    document.getElementById("search-input").addEventListener(
        "input",
        debounce((e) => {
            searchQuery = e.target.value;
            currentPage = 1;
            loadData();
        }, 500)
    );

    function debounce(func, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), delay);
        };
    }

    // Ініціалізація: завантажити конфігурацію, потім — дані
    fetchTableConfig();

    const changedRows = new Map();

    document.addEventListener("input", function(e) {
        if (e.target.tagName === "TD" || e.target.tagName === "INPUT") {
            const field = e.target.dataset.field;
            const id = e.target.dataset.id;
            const newValue = e.target.value || e.target.textContent;

            if (id && field) {
                if (!changedRows.has(id)) {
                    changedRows.set(id, {});
                }
                changedRows.get(id)[field] = newValue;
                document.getElementById("save-btn").style.display = "inline-block";
            }
        }
    });

    document.getElementById("save-btn").addEventListener("click", async () => {
        const entries = Array.from(changedRows.entries());

        for (const [id, fields] of entries) {
            const postData = {
                ...fields,
                id: id,
                put: true,
            };

            await fetch(apiUrl, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(postData),
            });
        }

        changedRows.clear();
        document.getElementById("save-btn").style.display = "none";
        await loadData(); // перезавантажити таблицю після збереження
    });

    document.getElementById("add-btn").addEventListener("click", async () => {
        const inputs = document.querySelectorAll("tr.table-success input");
        const postData = {
            add: true,
        };

        inputs.forEach((input) => {
            const field = input.dataset.field;
            postData[field] = input.value;
        });

        await fetch(apiUrl, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/json",
            },
            body: JSON.stringify(postData),
        });

        document.getElementById("add-btn").style.display = "none";
        await loadData(); // перезавантажити таблицю після додавання
    });
</script>