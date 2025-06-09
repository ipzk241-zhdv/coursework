<?php

namespace Utils;

class MapSVG
{
    protected static array $groups = [
        ['x' => 160, 'y' => 0, 'count' => 5, 'w' => 120, 'h' => 80, 'direction' => 'vertical', 'block' => 1, "reverse" => true],
        ['x' => 0, 'y' => 0, 'count' => 5, 'w' => 120, 'h' => 80, 'direction' => 'vertical', 'block' => 1, 'reverse' => false],
        ['x' => 0, 'y' => 440, 'count' => 14, 'w' => 80, 'h' => 120, 'direction' => 'horizontal', 'block' => 2],
        ['x' => 1000, 'y' => 0, 'count' => 5, 'w' => 120, 'h' => 80, 'direction' => 'vertical', 'block' => 3, 'reverse' => true],
        ['x' => 840, 'y' => 0, 'count' => 5, 'w' => 120, 'h' => 80, 'direction' => 'vertical', 'block' => 3, 'reverse' => false],
        ['x' => 720, 'y' => 320, 'count' => 1, 'w' => 120, 'h' => 80, 'direction' => 'vertical', 'block' => 4],
        ['x' => 400, 'y' => 280, 'count' => 2, 'w' => 160, 'h' => 120, 'direction' => 'horizontal', 'block' => 4, "reverse" => true],
        ['x' => 280, 'y' => 320, 'count' => 1, 'w' => 120, 'h' => 80, 'direction' => 'vertical', 'block' => 4],
    ];

    public static function getSlots(int $floor, array $rooms): array
    {
        $slots = [];
        $blockOffsets = [];
        $globalRoomIndex = 1; // індекс кімнати для користувача на поверсі

        foreach (self::$groups as $group) {
            $block = $group['block'];
            $offset = $blockOffsets[$block] ?? 0;
            $reverse = $group['reverse'] ?? false;
            $count = $group['count'];

            for ($i = 0; $i < $count; $i++) {
                $x = $group['x'] + ($group['direction'] === 'horizontal' ? $i * $group['w'] : 0);
                $y = $group['y'] + ($group['direction'] === 'vertical' ? $i * $group['h'] : 0);

                $indexInBlock = $reverse
                    ? $offset + ($count - 1 - $i)
                    : $offset + $i;

                // Ім’я кімнати в базі (унікальне і незмінне)
                $realRoomName = $floor * 1000 + $block * 100 + $indexInBlock;

                // Індекс для користувача має бути з урахуванням reverse
                $userIndex = $reverse
                    ? $globalRoomIndex + ($count - 1 - $i)
                    : $globalRoomIndex + $i;

                $userFriendlyName = $floor * 100 + $userIndex;

                // Пошук кімнати в БД
                $room = array_filter($rooms, fn($r) => $r['name'] == $realRoomName);
                $room = reset($room) ?: null;

                $slots[] = [
                    'x' => $x,
                    'y' => $y,
                    'w' => $group['w'],
                    'h' => $group['h'],
                    'name' => $userFriendlyName,        // показується користувачу
                    'room_id' => $room['id'] ?? null,   // ід з бази
                    'room_type' => $room['room_type'] ?? null,
                    'room_name' => $room['name'] ?? null, // з бази
                ];
            }

            $blockOffsets[$block] = $offset + $count;
            $globalRoomIndex += $count; // оновлюємо лічильник після групи
        }

        return $slots;
    }


    public static function generateSVG(array $slots): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1122 600" style="width:100%;height:auto">';
        foreach ($slots as $slot) {
            $fill = match ($slot['room_type'] ?? null) {
                'WC' => '#d0eaff',
                'кухня' => '#ffe4b3',
                'підсобне приміщення' => '#cccccc',
                'кімната' => '#b3ffc9',
                'зал відпочинку' => '#f9d0ff',
                'сходи' => '#e0e0e0',
                'пральня' => '#a3d5ff',
                'їдальня' => '#fff7b3',
                default => '#f0f0f0',
            };

            $svg .= sprintf(
                '
        <rect x="%d" y="%d" width="%d" height="%d" fill="%s" class="room" stroke="#000" data-id="%s" data-name="%s" data-type="%s" />',
                $slot['x'],
                $slot['y'],
                $slot['w'],
                $slot['h'],
                $fill,
                $slot['room_id'] ?? '',
                $slot['name'],
                $slot['room_type'] ?? ''
            );
            $svg .= sprintf(
                '<text x="%d" y="%d" font-size="12" text-anchor="middle" dominant-baseline="middle">%s</text>',
                $slot['x'] + $slot['w'] / 2,
                $slot['y'] + $slot['h'] / 2,
                $slot['name']
            );
        }
        return $svg . '
    </svg>';
    }
}
