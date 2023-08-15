<?php

namespace src;

class SessionManager {
    /**
     * Запуск сессии, если она еще не была начата.
     */
    public function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Установка значения в сессию.
     *
     * @param string $key Ключ.
     * @param mixed $value Значение.
     */
    public function set(string $key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Получение значения из сессии.
     *
     * @param string $key Ключ.
     * @return mixed Значение.
     */
    public function get(string $key) {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Проверка наличия ID сессии.
     */
    public function ensureClientSessionId() {
        if (!isset($_SESSION['clientSessionId'])) {
            $this->set('clientSessionId', session_create_id());
        }
    }
}
