<div class="modal fade show" style="display: block;" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Помилка <?= $code ?></h5>
            </div>
            <div class="modal-body">
                <p><?= htmlspecialchars($message) ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="$('.modal').remove()">Закрити</button>
            </div>
        </div>
    </div>
</div>