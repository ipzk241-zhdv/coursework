<?php

namespace controllers;

use classes\Controller;
use models\Users;
use classes\Request;
use classes\Core;

class UsersController extends Controller
{
    public function actionLogin()
    {
        $error = '';
        if (Request::method() === 'POST') {
            $email = trim(Request::post('email', ''));
            $password = Request::post('password', '');

            if ($email === '' || $password === '') {
                $error = 'Будь ласка, введіть email та пароль';
            } else {
                $user = Users::FindByLoginAndPassword($email, $password);
                if ($user) {
                    Users::LoginUser($user);
                    http_response_code(302);
                    header('Location: /');
                    exit;
                } else {
                    http_response_code(422);
                    $error = 'Невірний логін або пароль';
                }
            }
        }

        return $this->view('Login', ['error' => $error]);
    }

    public function actionRegister()
    {
        $errors = [];
        if (Request::method() === 'POST') {
            // потім змінить на реквест алл
            $name = trim(Request::post('name', ''));
            $lastName = trim(Request::post('lastName', ''));
            $patronymic = trim(Request::post('patronymic', ''));
            $email = trim(Request::post('email', ''));
            $password = Request::post('password', '');
            $confirmPassword = Request::post('confirm_password', '');

            $errors = Users::ValidateNewUser($name, $lastName, $patronymic, $email, $password, $confirmPassword);
            if (empty($errors)) {
                Users::RegisterUser($name, $lastName, $patronymic, $email, $password);
                http_response_code(302);
                header('Location: /users/login');
                exit;
            } else {
                http_response_code(422);
            }
        }

        // Передати помилки і попередні введені дані для повторного відображення форми
        return $this->view('Register', [
            'errors' => $errors,
            'name' => $name ?? '',
            'lastName' => $lastName ?? '',
            'patronymic' => $patronymic ?? '',
            'email' => $email ?? '',
        ]);
    }

    public function actionLogout()
    {
        Users::LogoutUser();
        header('Location: /');
        exit;
    }

    public function actionProfile()
    {
        if (Request::method() === 'POST') {
            $this->handleProfilePost();
        }
        return $this->view('Profile - Hostel', ["user" => Core::getInstance()->session->get('user')]);
    }

    private function handleProfilePost()
    {
        $session = Core::getInstance()->session;
        $id = $session->get('user')['id'];

        $user = Users::findById($id);
        if (!$user) {
            Core::log("Користувач не знайдений");
            return;
        }

        $data = Request::all();
        $data['id'] = $id; // додаємо id, обов'язково

        // Перевірка підтвердження пароля
        if (!password_verify($data['confirm_password'] ?? '', $user->password)) {
            Core::log("Невірний підтверджуючий пароль");
            return;
        }

        unset($data['confirm_password']);

        // Оновлення пароля, якщо задано
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']); // не оновлювати
        }

        // Обробка аватара
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatar = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $mimeType = mime_content_type($avatar['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                Core::log("Непідтримуваний тип зображення: $mimeType");
                return;
            }

            $avatarName = uniqid("avatar_") . '.' . pathinfo($avatar['name'], PATHINFO_EXTENSION);
            $uploadDir = __DIR__ . '/../public/users/';
            $uploadPath = $uploadDir . $avatarName;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (!move_uploaded_file($avatar['tmp_name'], $uploadPath)) {
                Core::log("Не вдалося завантажити аватар");
                return;
            }

            // Видалення старого аватара (якщо не default.png)
            if ($user->avatar !== 'default.png') {
                $oldAvatar = $uploadDir . $user->avatar;
                if (file_exists($oldAvatar)) {
                    Core::log("old avatar path");
                    Core::log($oldAvatar);
                    unlink($oldAvatar);
                }
            }

            $data['avatar'] = $avatarName;
        } else {
            unset($data['avatar']); // не оновлювати
        }

        // Фільтрація тільки дозволених полів
        $allowedKeys = ['id', 'name', 'lastname', 'patronymic', 'email', 'password', 'avatar', 'date_of_birth'];
        $data = array_filter(
            $data,
            fn($key) => in_array($key, $allowedKeys),
            ARRAY_FILTER_USE_KEY
        );

        // Оновлення
        $updatedUser = Users::apiUpdate($data);
        if (!$updatedUser) {
            Core::log("Оновлення не вдалося");
        }
        
        $user = Users::findById($id, true);
        unset($user['password']);
        Core::getInstance()->session->set("user", $user);
    }
}
