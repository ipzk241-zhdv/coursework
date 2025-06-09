function apiFetch(url, options = {}) {
    return fetch(url, options).then(handleAjaxResponse);
}

function handleAjaxResponse(response) {
    return response.json().then((data) => {
        if (data.status === "error") {
            showError(data.message || "Сталася помилка.", data.code || null);
            throw new Error(data.message || "AJAX error");
        }

        return data;
    });
}

function showError(message, code = null) {
    const containerId = "global-error-container";
    let container = document.getElementById(containerId);

    if (!container) {
        container = document.createElement("div");
        container.id = containerId;
        container.style.position = "fixed";
        container.style.top = "20px";
        container.style.right = "20px";
        container.style.zIndex = "9999";
        container.style.background = "#dc3545";
        container.style.color = "#fff";
        container.style.padding = "12px 20px";
        container.style.borderRadius = "6px";
        container.style.boxShadow = "0 0 10px rgba(0,0,0,0.2)";
        container.style.fontFamily = "sans-serif";
        container.style.maxWidth = "300px";
        document.body.appendChild(container);
    }

    container.innerText = (code ? `Помилка ${code}: ` : "") + message;

    // Автоматичне зникнення через 5 секунд
    setTimeout(() => {
        if (container) container.remove();
    }, 5000);
}

function apiFetchText(url, options = {}) {
    return fetch(url, options).then(response => {
      if (!response.ok) {
        showError(`Server responded with status ${response.status}`, response.status);
        throw new Error(`Server responded with status ${response.status}`);
      }
      return response.text();
    });
  }
  