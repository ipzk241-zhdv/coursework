let currentPage = 1;
let currentLimit = 10;
let currentSort = "id";
let currentDir = "asc";
let currentSearch = "";

function renderUsersTable(users) {
    let html = `<table class="table table-bordered table-hover">
            <thead>
                <tr>
                    ${renderSortableHeader("id", "ID")}
                    ${renderSortableHeader("lastname", "Прізвище")}
                    ${renderSortableHeader("name", "Ім’я")}
                    ${renderSortableHeader("role", "Роль")}
                    ${renderSortableHeader("email", "Email")}
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>`;

    if (users.length === 0) {
        html += `<tr><td colspan="6" class="text-center">Нічого не знайдено</td></tr>`;
    } else {
        for (const user of users) {
            html += `<tr>
                    <td>${user.id}</td>
                    <td>${user.lastname}</td>
                    <td>${user.name}</td>
                    <td>${user.role}</td>
                    <td>${user.email}</td>
                    <td><a href="/admin/editUser?id=${user.id}" class="btn btn-sm btn-primary">Редагувати</a></td>
                </tr>`;
        }
    }

    html += `</tbody></table>`;
    document.getElementById("usersTableContainer").innerHTML = html;
}

function renderSortableHeader(field, label) {
    const isActive = currentSort === field;
    const nextDir = isActive && currentDir === "asc" ? "desc" : "asc";
    const arrow = isActive ? (currentDir === "asc" ? " ▲" : " ▼") : "";
    return `<th><a href="#" onclick="changeSort('${field}', '${nextDir}'); return false;">${label}${arrow}</a></th>`;
}

function renderPagination({ page, pages }) {
    let html = "";

    for (let i = 1; i <= pages; i++) {
        html += `<li class="page-item ${i === page ? "active" : ""}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>`;
    }

    document.getElementById("paginationContainer").innerHTML = html;
}

function loadUsers() {
    loadUsersData(currentPage, currentLimit, currentSort, currentDir, currentSearch);
}

function changePage(page) {
    currentPage = page;
    loadUsers();
}

function changeSort(sort, dir) {
    currentSort = sort;
    currentDir = dir;
    currentPage = 1;
    loadUsers();
}

function loadUsersData(page, limit, sort, dir, search) {
    const params = new URLSearchParams({
        page,
        limit,
        sort,
        dir,
        search,
    });

    fetch(`/admin/users?${params.toString()}`, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                renderUsersTable(data.data);
                renderPagination(data.pagination);
            } else {
                console.error("Failed to load users");
            }
        })
        .catch((err) => console.error("AJAX error:", err));
}

document.getElementById("searchInput").addEventListener("input", function () {
    currentSearch = this.value;
    currentPage = 1;
    loadUsers();
});

document.getElementById("limitSelect").addEventListener("change", function () {
    currentLimit = parseInt(this.value);
    currentPage = 1;
    loadUsers();
});

document.addEventListener("DOMContentLoaded", () => {
    loadUsers();
});
