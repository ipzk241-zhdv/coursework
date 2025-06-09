<link rel="stylesheet" href="/public/css/map.css">

<div class="container py-5 my-5">
    <div class="container-fluid w-100">
        <div class="d-flex justify-content-center gap-2 mb-3 floor-selector">
            <input type="radio" class="btn-check" name="floor" id="floor1" autocomplete="off" value="1" checked>
            <label class="btn btn-outline-primary" for="floor1">Поверх 1</label>

            <input type="radio" class="btn-check" name="floor" id="floor2" autocomplete="off" value="2">
            <label class="btn btn-outline-primary" for="floor2">Поверх 2</label>

            <input type="radio" class="btn-check" name="floor" id="floor3" autocomplete="off" value="3">
            <label class="btn btn-outline-primary" for="floor3">Поверх 3</label>

            <input type="radio" class="btn-check" name="floor" id="floor4" autocomplete="off" value="4">
            <label class="btn btn-outline-primary" for="floor4">Поверх 4</label>

            <input type="radio" class="btn-check" name="floor" id="floor5" autocomplete="off" value="5">
            <label class="btn btn-outline-primary" for="floor5">Поверх 5</label>
        </div>

        <div id="map-container"></div>
        <div id="room-info-card" class="container"></div>
    </div>
</div>

<script src="/public/js/ajax-error-handler.js"></script>
<script src="/public/js/map.js"></script>