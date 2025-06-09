<div class="container mt-4">
    <h1>Налаштування</h1>
    <form id="settingsForm">
        <div class="mb-3">
            <label for="currentLayoutId" class="form-label">Поточний Layout</label>
            <select class="form-select" id="currentLayoutId" name="current_layout_id" required></select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="toCache" name="to_cache" />
            <label class="form-check-label" for="toCache">Використовувати кеш</label>
        </div>

        <div class="mb-3">
            <label for="cacheLifetime" class="form-label">Тривалість кешу (секунди)</label>
            <input type="number" class="form-control" id="cacheLifetime" name="cache_lifetime" min="0" />
        </div>

        <div class="mb-3">
            <label for="excludeCache" class="form-label">Методи, виключені з кешування</label>
            <textarea class="form-control" id="excludeCache" name="exclude_cache" rows="3" placeholder="Наприклад: ['method1', 'method2']"></textarea>
            <div class="form-text">Введіть масив методів</div>
        </div>

        <button type="submit" class="btn btn-primary">Зберегти налаштування</button>
    </form>
</div>

<script src="/public/js/ajax-error-handler.js"></script>
<script>
    async function loadSettings() {
        return apiFetch('/admin/settings', {
            method: 'GET',
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        });
    }

    async function updateSettings(data) {
        if (!checkJson(data.exclude_cache)) {
            alert("Невірний формат JSON у полі 'Методи, виключені з кешування'");
            throw new Error("Invalid JSON in exclude_cache");
        }

        return apiFetch('/admin/settings', {
            method: 'POST',
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        });
    }

    function checkJson(str) {
        if (typeof str !== "string") return false;
        try {
            JSON.parse(str);
            return true;
        } catch {
            return false;
        }
    }

    function arrayToBracketString(arr) {
        if (!Array.isArray(arr)) return '';
        return '[' + arr.map(s => `'${s.replace(/'/g, "\\'")}'`).join(', ') + ']';
    }

    // Парсимо рядок виду ['action1', 'action2'] у масив
    function bracketStringToArray(str) {
        if (typeof str !== 'string') return [];
        const regex = /'((?:\\'|[^'])*)'/g;
        const result = [];
        let match;
        while ((match = regex.exec(str)) !== null) {
            result.push(match[1].replace(/\\'/g, "'"));
        }
        return result;
    }

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const settings = await loadSettings();

            // Заповнюємо select з layouts
            const layoutSelect = document.getElementById('currentLayoutId');
            layoutSelect.innerHTML = '';
            for (const layout of settings.layouts.available) {
                const option = document.createElement('option');
                option.value = layout.id;
                option.textContent = layout.path;
                if (layout.id === settings.layouts.current_layout_id) {
                    option.selected = true;
                }
                layoutSelect.appendChild(option);
            }

            document.getElementById('toCache').checked = !!settings.to_cache;
            document.getElementById('cacheLifetime').value = settings.cache_lifetime || '';

            let excludeCacheValue = '';
            try {
                const arr = JSON.parse(settings.exclude_cache);
                excludeCacheValue = arrayToBracketString(arr);
            } catch {
                excludeCacheValue = '';
            }
            document.getElementById('excludeCache').value = excludeCacheValue;

        } catch (err) {
            console.error('Помилка завантаження налаштувань:', err);
        }
    });

    document.getElementById('settingsForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const excludeCacheRaw = document.getElementById('excludeCache').value.trim();
        const excludeCacheArr = bracketStringToArray(excludeCacheRaw);

        // Перевірка, що всі елементи — рядки
        if (!excludeCacheArr.every(s => typeof s === 'string')) {
            alert("Невірний формат у полі 'Методи, виключені з кешування'");
            return;
        }

        const data = {
            id: 1,
            put: 1,
            current_layout_id: Number(document.getElementById('currentLayoutId').value),
            to_cache: document.getElementById('toCache').checked,
            cache_lifetime: Number(document.getElementById('cacheLifetime').value),
            exclude_cache: JSON.stringify(excludeCacheArr),
        };

        try {
            await updateSettings(data);
            alert('Налаштування успішно збережено!');
        } catch (err) {
            console.error('Помилка збереження налаштувань:', err);
        }
    });
</script>