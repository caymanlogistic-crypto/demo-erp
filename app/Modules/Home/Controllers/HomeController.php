<?php

declare(strict_types=1);

namespace App\Modules\Home\Controllers;

class HomeController
{
    /**
     * Главная страница «Общая информация».
     */
    public function index(): string
    {
        return $this->renderIndex();
    }

    private function renderIndex(): string
    {
        ob_start();
        $title      = 'Общая информация — Demo ERP';
        $pageTitle  = 'Общая информация';
        $pageModule = 'home';
        require __DIR__ . '/../../../Views/home/index.php';
        $content = ob_get_clean();

        ob_start();
        $title      = $title;
        $pageTitle  = $pageTitle;
        $pageModule = $pageModule;
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }
}
