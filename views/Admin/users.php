<div class="container mt-4">
    <h2>Користувачі</h2>

    <!-- Панель пошуку та ліміту -->
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Пошук...">
        </div>
        <div class="col-md-2">
            <select id="limitSelect" class="form-control">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <!-- Таблиця -->
    <div id="usersTableContainer"></div>

    <!-- Пагінація -->
    <nav>
        <ul id="paginationContainer" class="pagination"></ul>
    </nav>
</div>

<script src="/public/js/admin-users.js"></script>