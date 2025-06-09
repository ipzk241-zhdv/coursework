document.addEventListener("DOMContentLoaded", () => {
    const previewPane = document.getElementById("previewPane");
    const container = document.querySelector(".container-fluid");
    const selectedName = container ? container.dataset.selected : null;

    const attachClickHandlers = () => {
        const listItems = document.querySelectorAll(".component-item");

        listItems.forEach((item) => {
            item.addEventListener("click", () => {
                const name = item.dataset.name;
                const exists = item.dataset.exists === "1";
                const description = item.dataset.description || "";

                fetch(`/admin/renderModule?name=${encodeURIComponent(name)}`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                    .then((res) => res.text())
                    .then((html) => {
                        const safeHtml = html.trim() !== "" ? html : '<div class="text-danger">Модуль не має візуалізації або виникла помилка</div>';

                        let formButtons = "";

                        if (exists) {
                            formButtons = `
                                <input type="submit" name="put" value="Редагувати опис" class="btn btn-warning">
                                <input type="submit" name="delete" value="Видалити" class="btn btn-danger">
                            `;
                        } else {
                            formButtons = `
                                <input type="submit" name="add" value="Додати" class="btn btn-success">
                            `;
                        }

                        const fullHTML = `
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light w-100">
                                <h5 class="mb-0">${name}</h5>
                                <form method="post" class="ms-3 d-flex flex-column">
                                    <input type="hidden" name="name" value="${name}">
                                    <div class="mb-2">
                                        <label for="desc-${name}" class="form-label mb-1">Опис (до 100 символів):</label>
                                        <input type="text" id="desc-${name}" name="description" maxlength="100"
                                            class="form-control" value="${description}">
                                    </div>
                                    <div class="d-flex gap-2">
                                        ${formButtons}
                                    </div>
                                </form>
                            </div>
                            <div class="p-3">${safeHtml}</div>
                        `;

                        previewPane.innerHTML = fullHTML;
                    })
                    .catch(() => {
                        previewPane.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                                <h5 class="mb-0">${name}</h5>
                                <div class="text-danger">Помилка під час завантаження</div>
                            </div>
                        `;
                    });
            });
        });
    };

    attachClickHandlers();

    // Якщо selectedName є, програмно викликаємо клік на відповідному елементі
    if (selectedName) {
        const selectedItem = document.querySelector(`.component-item[data-name="${selectedName}"]`);
        if (selectedItem) {
            selectedItem.click();
        }
    }
});